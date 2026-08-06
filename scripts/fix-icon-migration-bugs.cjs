/**
 * Fix corrupted icon migration artifacts.
 *
 * Default: report-only (no writes). Re-running is safe on healthy files.
 * Apply:    node scripts/fix-icon-migration-bugs.cjs --write
 */
const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const root = path.resolve(__dirname, '..');
const WRITE = process.argv.includes('--write') || process.argv.includes('--fix');

const stats = { checked: 0, wouldFix: 0, fixed: 0, skipped: 0 };

function logSkip(label, reason) {
    stats.skipped += 1;
    console.log(`skip  ${label} — ${reason}`);
}

function logAction(label, detail) {
    stats.wouldFix += 1;
    if (WRITE) {
        stats.fixed += 1;
        console.log(`fixed ${label}${detail ? ' — ' + detail : ''}`);
    } else {
        console.log(`would-fix ${label}${detail ? ' — ' + detail : ''}`);
    }
}

function writeIfAllowed(file, content) {
    if (WRITE) {
        fs.writeFileSync(file, content);
    }
}

function gitShow(rev, file) {
    return execSync(`git show ${rev}:${file}`, { cwd: root, encoding: 'utf8', maxBuffer: 10 * 1024 * 1024 });
}

function migrateJsTailIcons(text) {
    return text
        .replace(/btn\.innerHTML = '<i class="fas fa-sync-alt"><\/i> Get Emails'/g, "btn.innerHTML = crmIcon('sync-alt') + ' Get Emails'")
        .replace(/btn\.innerHTML = '<i class="fas fa-sync-alt"><\/i> Get Drafts'/g, "btn.innerHTML = crmIcon('sync-alt') + ' Get Drafts'")
        .replace(/newMailBanner\.innerHTML = '<i class="fas fa-envelope"><\/i> '/g, "newMailBanner.innerHTML = crmIcon('envelope') + ' '")
        .replace(/btn\.innerHTML = '<i class="fas fa-spinner fa-spin"><\/i> Loading\.\.\.'/g, "btn.innerHTML = crmIconSpinner(' Loading...')")
        .replace(/saveDraftBtn\.innerHTML\s*=\s*'<i class="fas fa-spinner fa-spin"><\/i> Saving[^']*'/g, "saveDraftBtn.innerHTML = crmIconSpinner(' Saving…')")
        .replace(/saveDraftBtn\.innerHTML = '<i class="fas fa-save"><\/i> Save Draft'/g, "saveDraftBtn.innerHTML = crmIcon('save') + ' Save Draft'")
        .replace(/sendBtn\.innerHTML = '<i class="fas fa-spinner fa-spin"><\/i> Sending[^']*'/g, "sendBtn.innerHTML = crmIconSpinner(' Sending…')")
        .replace(/sendBtn\.innerHTML = '<i class="fas fa-paper-plane"><\/i> Send'/g, "sendBtn.innerHTML = crmIcon('paper-plane') + ' Send'");
}

/** Elite inbox: only restore from old rev when clear corruption / pre-migration icons remain. */
function eliteInboxNeedsFix(content) {
    if (content.includes("crmIconSpinner(' }}')")) {
        return 'corruption marker crmIconSpinner(\' }}\')';
    }
    if (content.includes("@icon('{{")) {
        return 'corruption marker @icon(\'{{';
    }
    // Pre-migration Font Awesome HTML still in inbox JS (script intended to migrate these)
    if (
        content.includes('<i class="fas fa-sync-alt"></i> Get Emails') ||
        content.includes('<i class="fas fa-sync-alt"></i> Get Drafts') ||
        content.includes('<i class="fas fa-spinner fa-spin"></i> Loading...') ||
        content.includes('<i class="fas fa-paper-plane"></i> Send')
    ) {
        return 'legacy <i class="fas …"> icon HTML in inbox JS';
    }
    return null;
}

function fixEliteInbox() {
    const rel = 'resources/views/elite/emails-inbox.blade.php';
    const file = path.join(root, rel);
    stats.checked += 1;

    if (!fs.existsSync(file)) {
        logSkip(rel, 'file missing');
        return;
    }

    const currentText = fs.readFileSync(file, 'utf8');
    const reason = eliteInboxNeedsFix(currentText);
    if (!reason) {
        logSkip(rel, 'healthy (no corruption / legacy FA markers)');
        return;
    }

    let original;
    try {
        original = gitShow('5dbe73a9', rel).split(/\r?\n/);
    } catch (e) {
        console.warn(`warn  ${rel} — could not load git rev: ${e.message}`);
        return;
    }

    const current = currentText.split(/\r?\n/);
    const head = current.slice(0, 1211); // through params.push('sync=1');
    head.push("        if (!silent && btn) { btn.disabled = true; btn.innerHTML = crmIconSpinner(' Syncing...'); }");
    let tail = original.slice(1213, 1990).join('\n'); // if (silent) inboxFetchInFlight ... through }());
    tail = migrateJsTailIcons(tail);
    const restored = [...head, tail, ''].join('\n');

    writeIfAllowed(file, restored);
    logAction(rel, reason);
}

function fixRecentClients() {
    const rel = 'resources/views/AdminConsole/recent_clients/index.blade.php';
    const file = path.join(root, rel);
    stats.checked += 1;

    if (!fs.existsSync(file)) {
        logSkip(rel, 'file missing');
        return;
    }

    const current = fs.readFileSync(file, 'utf8');
    if (!current.includes("html += crmIconSpinner(' }}')")) {
        logSkip(rel, 'healthy (corruption marker not found)');
        return;
    }

    let original;
    try {
        original = gitShow('b2e4679e', rel);
    } catch (e) {
        console.warn(`warn  ${rel} — could not load git rev: ${e.message}`);
        return;
    }

    const origLines = original.split(/\r?\n/);
    const restoredBlock = origLines.slice(760, 846).join('\n')
        .replace(
            /html \+= '<i class="far fa-calendar"><\/i> '/g,
            "html += crmIcon('calendar', 'regular') + ' '"
        )
        .replace(
            /html \+= ' \| <i class="far fa-user"><\/i> '/g,
            "html += ' | ' + crmIcon('user', 'regular') + ' '"
        )
        .replace(
            /html \+= '<h6><i class="fas fa-file"><\/i> Documents<\/h6>'/g,
            "html += '<h6>' + crmIcon('file') + ' Documents</h6>'"
        )
        .replace(
            /<i class="fas fa-cloud-upload-alt"><\/i> Upload All These Docs to S3/g,
            "' + crmIcon('cloud-upload-alt') + ' Upload All These Docs to S3"
        )
        .replace(
            /html \+= '<h6><i class="fas fa-archive"><\/i> Actions<\/h6>'/g,
            "html += '<h6>' + crmIcon('archive') + ' Actions</h6>'"
        )
        .replace(
            /html \+= '<i class="fas fa-undo"><\/i> Unarchive Client'/g,
            "html += crmIcon('undo') + ' Unarchive Client'"
        )
        .replace(
            /html \+= '<span class="ml-2 text-muted"><i class="fas fa-info-circle"><\/i> This client is currently archived<\/span>'/g,
            "html += '<span class=\"ml-2 text-muted\">' + crmIcon('info-circle') + ' This client is currently archived</span>'"
        )
        .replace(
            /html \+= '<i class="fas fa-archive"><\/i> Archive Client'/g,
            "html += crmIcon('archive') + ' Archive Client'"
        )
        .replace(
            /html \+= '<span class="ml-2 text-muted"><i class="fas fa-info-circle"><\/i> Archive this client to move it to archived clients<\/span>'/g,
            "html += '<span class=\"ml-2 text-muted\">' + crmIcon('info-circle') + ' Archive this client to move it to archived clients</span>'"
        );

    const marker = "\t\t\t\t\t\thtml += '<div class=\"text-muted small\">';\n\t\t\t\t\t\thtml += crmIconSpinner(' }}'),";
    const replacement = "\t\t\t\t\t\thtml += '<div class=\"text-muted small\">';\n" + restoredBlock.split('\n').slice(1).join('\n');

    let fixed;
    if (!current.includes(marker.split('\n')[0])) {
        const start = current.indexOf("\t\t\t\t\t\thtml += '<div class=\"text-muted small\">';");
        const end = current.indexOf('\t\t\t\t\t\t$container.html(html);');
        if (start === -1 || end === -1) {
            console.warn(`warn  ${rel} — could not locate block boundaries`);
            return;
        }
        fixed = current.slice(0, start) + restoredBlock + current.slice(end);
    } else {
        fixed = current.replace(marker, replacement);
    }

    writeIfAllowed(file, fixed);
    logAction(rel, 'corruption marker crmIconSpinner(\' }}\')');
}

/** In backtick template literals, ' + crmIcon(...) + ' is literal text — use ${crmIcon(...)}.
 *  Applied per line so we never rewrite normal string concatenation outside backticks.
 */
function fixTemplateLiteralCrmIcons(content) {
    const re = /(`(?:\\`|[^`])*)' \+ crmIcon\(([^)]+(?:\([^)]*\)[^)]*)*)\) \+ '((?:\\`|[^`])*)`/g;
    return content.split('\n').map(function (line) {
        if (line.indexOf('`') === -1 || line.indexOf("' + crmIcon(") === -1) {
            return line;
        }
        return line.replace(
            re,
            function (match, before, args, after) {
                return before + '${crmIcon(' + args + ')}' + after + '`';
            }
        );
    }).join('\n');
}

function fixJsTemplateLiterals() {
    const files = [
        'public/js/pages/admin/client-detail/document-categories.js',
        'public/js/pages/admin/client-detail/document-signature.js',
        'public/js/pages/admin/client-detail/document-actions.js',
        'public/js/pages/admin/client-detail/blade-inline.js',
        'public/js/pages/admin/partner-detail/invoice-handlers.js',
        'public/js/common/document-handlers.js',
        'public/js/emails_v2.js',
    ];

    for (const rel of files) {
        const file = path.join(root, rel);
        stats.checked += 1;
        if (!fs.existsSync(file)) {
            logSkip(rel, 'file missing');
            continue;
        }
        const original = fs.readFileSync(file, 'utf8');
        let content = fixTemplateLiteralCrmIcons(original);

        if (rel.endsWith('document-actions.js')) {
            content = content.replace(
                /trRow \+= "<tr class='drow' id='id_"+subArray\.id+"'><td>"\+subArray\.checklist+"<\/td><td>"\+ res\.Added_By \+ "<br>" \+ res\.Added_date+"<\/td><td><a target='_blank' class='dropdown-item' href='"+subArray\.myfile+"'>' \+ crmIcon\('file-image'\) \+ ' <span>"\+subArray\.file_name\+'\.'\+subArray\.filetype+"<\/span><\/a><\/div><\/td><td>"\+res\.Verified_By\+ "<br>" \+res\.Verified_At+"<\/td><\/tr>";/,
                'trRow += "<tr class=\'drow\' id=\'id_"+subArray.id+"\'><td>"+subArray.checklist+"</td><td>"+ res.Added_By + "<br>" + res.Added_date+"</td><td><a target=\'_blank\' class=\'dropdown-item\' href=\'"+subArray.myfile+"\'>"+crmIcon(\'file-image\')+" <span>"+subArray.file_name+\'.\'+subArray.filetype+"</span></a></td><td>"+res.Verified_By+ "<br>" +res.Verified_At+"</td></tr>";'
            );
            content = content.replace(
                /trRow \+= "<tr class='drow' id='id_"+subArray\.id+"'><td>"\+subArray\.checklist+"<\/td><td>"\+ res\.Added_By \+ "<br>" \+ res\.Added_date+"<\/td><td>' \+ crmIcon\('file-image'\) \+ ' <span>"\+subArray\.file_name\+'\.'\+subArray\.filetype+"<\/span><\/div><\/td><td>"\+res\.Verified_By\+ "<br>" \+res\.Verified_At+"<\/td><\/tr>";/,
                'trRow += "<tr class=\'drow\' id=\'id_"+subArray.id+"\'><td>"+subArray.checklist+"</td><td>"+ res.Added_By + "<br>" + res.Added_date+"</td><td>"+crmIcon(\'file-image\')+" <span>"+subArray.file_name+\'.\'+subArray.filetype+"</span></td><td>"+res.Verified_By+ "<br>" +res.Verified_At+"</td></tr>";'
            );
        }

        if (content === original) {
            logSkip(rel, 'healthy (no template-literal crmIcon issues)');
            continue;
        }

        writeIfAllowed(file, content);
        logAction(rel, 'template-literal crmIcon patterns');
    }
}

function fixSignaturesShow() {
    const rel = 'resources/views/crm/signatures/show.blade.php';
    const file = path.join(root, rel);
    stats.checked += 1;

    if (!fs.existsSync(file)) {
        logSkip(rel, 'file missing');
        return;
    }

    const original = fs.readFileSync(file, 'utf8');
    if (!original.includes("@icon('{{")) {
        logSkip(rel, 'healthy (no @icon(\'{{ corruption)');
        return;
    }

    let content = original.replace(
        /@icon\('\{\{', 'solid', \['class' => '\$signer->status === 'pending' \? 'clock' : \(\$signer->status === 'signed' \? 'check' : 'times'\) }}'\]\)/,
        "@icon($signer->status === 'pending' ? 'clock' : ($signer->status === 'signed' ? 'check' : 'times'))"
    );
    content = content.replace(
        /@icon\('\{\{', 'solid', \['class' => '\$icon }}'\]\)/,
        '@icon($icon)'
    );

    if (content === original) {
        logSkip(rel, 'corruption pattern present but no known replace matched');
        return;
    }

    writeIfAllowed(file, content);
    logAction(rel, '@icon(\'{{ corruption');
}

function fixExplanationCircle() {
    const files = [
        'resources/views/Admin/clients/addclientmodal.blade.php',
        'resources/views/Admin/partners/addpartnermodal.blade.php',
        'resources/views/Admin/products/addproductmodal.blade.php',
    ];
    for (const rel of files) {
        const file = path.join(root, rel);
        stats.checked += 1;
        if (!fs.existsSync(file)) {
            logSkip(rel, 'file missing');
            continue;
        }
        const original = fs.readFileSync(file, 'utf8');
        if (!original.includes("@icon('explanation-circle')")) {
            logSkip(rel, 'healthy (no explanation-circle)');
            continue;
        }
        const content = original.replace(/@icon\('explanation-circle'\)/g, "@icon('info-circle')");
        writeIfAllowed(file, content);
        logAction(rel, "replace @icon('explanation-circle')");
    }
}

function fixMinimalLayoutScripts() {
    const rel = 'resources/js/minimal-layout-scripts.js';
    const file = path.join(root, rel);
    stats.checked += 1;

    if (!fs.existsSync(file)) {
        logSkip(rel, 'file missing');
        return;
    }

    const original = fs.readFileSync(file, 'utf8');
    if (original.includes("@legacy/common/crm-icon.js")) {
        logSkip(rel, 'healthy (crm-icon.js already imported)');
        return;
    }

    const content = `/**
 * Minimal layout scripts (login, outlook) — Vite entry (Phase 2f).
 */
'use strict';

import '@legacy/common/crm-icon.js';
import '@legacy/scripts.js';
import '@legacy/custom.js';
`;
    writeIfAllowed(file, content);
    logAction(rel, 'add crm-icon.js import');
}

console.log(WRITE
    ? 'Mode: WRITE (--write). Applying fixes where corruption is detected.\n'
    : 'Mode: REPORT (default). No files will be modified. Pass --write to apply.\n');

fixEliteInbox();
fixRecentClients();
fixSignaturesShow();
fixExplanationCircle();
fixMinimalLayoutScripts();
fixJsTemplateLiterals();

console.log(`\nSummary: checked=${stats.checked} skipped=${stats.skipped} wouldFix=${stats.wouldFix}` +
    (WRITE ? ` fixed=${stats.fixed}` : ' (dry-run)'));
if (!WRITE && stats.wouldFix > 0) {
    console.log('Re-run with --write to apply the reported fixes.');
}
