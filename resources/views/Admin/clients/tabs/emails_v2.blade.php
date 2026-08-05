<!-- Emails V2 Interface - Generic for Clients and Partners -->

@php
    // Cache-busting: bump when emails_v2.js changes; emails_v2.css uses mtime so it updates automatically
    $emailsV2AssetVer = 14;
    $emailsV2CssVer = @filemtime(public_path('css/emails_v2.css')) ?: $emailsV2AssetVer;
@endphp
<!-- Email V2 Styles -->
<link rel="stylesheet" href="{{ asset('css/emails_v2.css') }}?v={{ $emailsV2CssVer }}">

@php
    // Support both $client and $fetchedData variable names
    $entityData = $client ?? $partner ?? $fetchedData ?? null;
    
    // Determine entity type (client, lead, or partner)
    $entityType = 'client'; // default
    if (isset($partner)) {
        $entityType = 'partner';
    } elseif (isset($fetchedData)) {
        // Check if it's a Partner model
        if (isset($fetchedData->partner_name) || (method_exists($fetchedData, 'getTable') && $fetchedData->getTable() === 'partners')) {
            $entityType = 'partner';
        } else {
            // Admin: use actual type (client or lead) so sent emails match
            $entityType = $fetchedData->type ?? 'client';
        }
    }

    $emailUploadExtensions = config('crm.email_upload_allowed_extensions', ['msg', 'eml']);
    $emailUploadAccept = collect($emailUploadExtensions)->map(fn ($e) => '.' . ltrim($e, '.'))->implode(',');
    $emailUploadLabel = collect($emailUploadExtensions)->map(fn ($e) => '.' . ltrim($e, '.'))->implode(', ');
@endphp
<div class="email-v2-interface-container" 
     data-entity-id="{{ $entityData->id ?? '' }}" 
     data-entity-type="{{ $entityType }}"
     data-show-email-category="{{ $entityType !== 'partner' ? '1' : '0' }}"
     data-user-role="{{ Auth::user()->role ?? '' }}"
     data-email-upload-accept="{{ $emailUploadAccept }}">
    @if($entityType !== 'partner')
    <!-- Client detail only: Client | College sub-tabs -->
    <div class="email-v2-category-tabs" role="tablist" aria-label="Email category">
        <button type="button" class="category-tab-btn active" data-category="client" id="category-tab-client" aria-selected="true">
            @icon('user') Client
        </button>
        <button type="button" class="category-tab-btn" data-category="college" id="category-tab-college" aria-selected="false">
            @icon('university') College
        </button>
    </div>
    @endif

    <!-- Outlook-style main content: list pane + reading pane -->
    <div class="email-v2-main-content">
        <div class="email-v2-list-pane">
            <div class="list-toolbar">
                <div class="email-v2-folder-tabs folder-tabs" role="tablist" aria-label="Mail folders">
                    <button type="button" class="folder-tab-btn folder-item active" data-folder="inbox" id="folder-tab-inbox" aria-selected="true">
                        @icon('inbox') Inbox
                    </button>
                    <button type="button" class="folder-tab-btn folder-item" data-folder="sent" id="folder-tab-sent" aria-selected="false">
                        @icon('paper-plane') Sent Items
                    </button>
                </div>
                <select id="mailTypeFilterV2" class="filter-select" style="display:none;" aria-hidden="true">
                    <option value="inbox" selected>Inbox</option>
                    <option value="sent">Sent</option>
                </select>
            </div>

            <div id="upload-area-v2" class="inline-drop-zone drag-drop-zone" role="button" tabindex="0" aria-label="Upload email files">
                @icon('cloud-upload-alt', 'solid', ['class' => 'inline-drop-zone-icon'])
                <span>Drag &amp; drop Outlook email files ({{ $emailUploadLabel ?? $emailUploadAccept ?? '.msg' }}) here or <b>browse</b> to upload</span>
                <div id="file-count-v2" class="file-count-badge">0</div>
                <input type="file" id="emailV2FileInput" class="file-input" accept="{{ $emailUploadAccept }}" multiple style="display: none;">
            </div>
            <div id="upload-progress-v2" class="upload-progress">
                <span id="fileStatusV2">Ready to upload</span>
            </div>
            <div id="pythonServiceWarningV2" class="email-v2-python-warning" style="display: none;" role="status" aria-live="polite"></div>

            <div class="list-header">
                <div class="list-header-row">
                    <div class="search-box">
                        @icon('search', 'solid', ['class' => 'search-box-icon'])
                        <input type="text" id="emailV2SearchInput" placeholder="Search emails..." aria-label="Search emails">
                    </div>
                </div>
                <div class="list-header-filters">
                    <select id="labelV2Filter" class="list-filter-select" aria-label="Filter by label">
                        <option value="">All Labels</option>
                    </select>
                </div>
            </div>

            <div class="email-v2-list email-list" id="emailListV2">
                <div class="empty-state empty-state--list">
                    <div class="empty-state-icon">@icon('inbox')</div>
                    <div class="empty-state-text">
                        <h3>No emails found</h3>
                        <p>Upload {{ $emailUploadLabel ?? $emailUploadAccept ?? '.msg' }} files above to get started.</p>
                    </div>
                </div>
            </div>

            <div class="pagination-bar">
                <span id="pageInfoV2">Showing 0</span>
                <span id="resultsCountV2" class="visually-hidden">0 results</span>
                <div class="pagination-controls">
                    <button type="button" class="pagination-btn" id="prevBtnV2" aria-label="Previous page">@icon('chevron-left')</button>
                    <button type="button" class="pagination-btn" id="nextBtnV2" aria-label="Next page">@icon('chevron-right')</button>
                </div>
            </div>
        </div>

        <div class="email-v2-content-pane email-v2-reading-pane">
            <div class="empty-state" id="emailContentPlaceholderV2">
                @icon('envelope-open', 'regular', ['class' => 'empty-state-envelope-icon'])
                <p>Select an item to read</p>
            </div>

            <div class="reading-pane-content" id="emailContentViewV2">
                <div class="reading-header">
                    <div class="action-bar">
                        <button type="button" class="action-btn" id="btnReplyV2">@icon('reply') Reply</button>
                        <button type="button" class="action-btn" id="btnReplyAllV2">@icon('reply') Reply All</button>
                        <button type="button" class="action-btn" id="btnForwardV2">@icon('share') Forward</button>
                        <button type="button" class="action-btn action-btn--danger" id="btnDeleteEmailV2" style="display: none;">@icon('trash') Delete</button>
                    </div>
                    <h2 class="email-full-subject" id="readSubjectV2"></h2>
                    <div class="email-meta">
                        <div class="sender-avatar" id="readAvatarV2" aria-hidden="true"></div>
                        <div class="meta-details">
                            <div class="meta-sender" id="readSenderV2"></div>
                            <div class="meta-recipients" id="readToV2"></div>
                            <div class="meta-recipients meta-cc" id="readCcV2" hidden></div>
                        </div>
                        <div class="meta-date" id="readDateV2"></div>
                    </div>
                </div>
                <div id="attachmentsContainerV2" class="email-attachments-container reading-attachments" hidden></div>
                <div class="reading-body">
                    <iframe id="emailReadBodyV2" class="email-read-body-iframe" title="Email content" sandbox="allow-same-origin"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Attachment Preview Modal -->
<div id="attachmentPreviewModalV2" class="preview-modal" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="previewFileNameV2">
    <div class="preview-modal-overlay" id="previewOverlayV2"></div>
    <div class="preview-modal-content">
        <div class="preview-modal-header">
            <h3 id="previewFileNameV2">Preview</h3>
            <button class="preview-close" id="closePreviewBtnV2" aria-label="Close preview">&times;</button>
        </div>
        <div class="preview-modal-body">
            <div class="preview-loading" id="previewLoadingV2">
                @icon('spinner', 'solid', ['spin' => true])
                <span>Loading preview&hellip;</span>
            </div>
            <iframe id="previewFrameV2" src="" style="display:none;"></iframe>
        </div>
    </div>
</div>

<!-- Email Context Menu -->
<div id="emailContextMenuV2" class="email-context-menu" style="display: none;">
    <div class="context-menu-item" data-action="apply-label">
        @icon('tag')
        <span>Apply Label</span>
        @icon('chevron-right', 'solid', ['class' => 'context-menu-arrow'])
    </div>
    <div class="context-menu-item" data-action="reply">
        @icon('reply')
        <span>Reply</span>
    </div>
    <div class="context-menu-item" data-action="forward">
        @icon('share')
        <span>Forward</span>
    </div>
    <div class="context-menu-separator"></div>
    <div class="context-menu-item" data-action="delete" style="display: none;">
        @icon('trash')
        <span>Delete</span>
    </div>
</div>

<!-- Label Submenu -->
<div id="labelSubmenuV2" class="email-context-submenu" style="display: none;">
    <div class="submenu-header">
        @icon('arrow-left', 'solid', ['class' => 'submenu-back'])
        <span>Select Label</span>
    </div>
    <div class="submenu-content" id="labelSubmenuContentV2">
        <!-- Labels will be populated dynamically -->
    </div>
</div>

<!-- Context Menu Overlay (for closing menu on outside click) -->
<div id="contextMenuOverlayV2" class="context-menu-overlay" style="display: none;"></div>

<!-- Email upload loading overlay -->
<div class="email-upload-loading-overlay" id="emailUploadLoadingOverlayV2" aria-hidden="true" aria-live="polite" aria-busy="false">
    <div class="email-upload-loading-card" role="status">
        <div class="email-upload-loading-icon" aria-hidden="true">
            @icon('envelope')
            <span class="email-upload-loading-spinner"></span>
        </div>
        <h3 class="email-upload-loading-title" id="emailUploadLoadingTitleV2">Uploading email</h3>
        <p class="email-upload-loading-message" id="emailUploadLoadingMessageV2">Please wait while your email is being processed…</p>
        <p class="email-upload-loading-filename" id="emailUploadLoadingFilenameV2"></p>
        <div class="email-upload-loading-progress" aria-hidden="true">
            <div class="email-upload-loading-progress-bar" id="emailUploadLoadingProgressBarV2"></div>
        </div>
        <p class="email-upload-loading-hint">Do not close or refresh this page</p>
    </div>
</div>

<!-- Attachment storage modal (client uploads only — skipped for partners) -->
<div class="attachment-storage-modal-overlay" id="attachmentStorageModalV2" aria-hidden="true">
    <div class="attachment-storage-modal" role="dialog" aria-labelledby="attachmentStorageModalTitleV2" aria-modal="true">
        <div class="attachment-storage-modal__header">
            <h3 id="attachmentStorageModalTitleV2">Save Attachments</h3>
            <p class="attachment-storage-modal__subtitle" id="attachmentStorageSubtitleV2">Rename files before saving. Optionally copy to the Documents tab.</p>
            <span class="attachment-storage-modal__count" id="attachmentStorageCountV2" aria-live="polite"></span>
        </div>
        <div class="attachment-storage-destination" id="attachmentStorageDestinationV2">
            <label class="attachment-storage-checkbox">
                <input type="checkbox" id="attachmentSaveToDocumentsV2">
                Also save copies to Documents tab
            </label>
            <select id="attachmentDocumentCategoryV2" class="attachment-storage-select" aria-label="Document category" disabled>
                <option value="">Select category…</option>
            </select>
        </div>
        <div class="attachment-storage-per-email" id="attachmentStoragePerEmailV2" hidden></div>
        <div class="attachment-storage-table-wrap">
            <table class="attachment-storage-table">
                <thead>
                    <tr>
                        <th scope="col">File</th>
                        <th scope="col">Size</th>
                        <th scope="col">Save as</th>
                    </tr>
                </thead>
                <tbody id="attachmentStorageModalBodyV2"></tbody>
            </table>
        </div>
        <div class="attachment-storage-modal__actions">
            <button type="button" class="attachment-storage-modal__btn attachment-storage-modal__btn--cancel" id="attachmentStorageCancelV2">Cancel upload</button>
            <button type="button" class="attachment-storage-modal__btn attachment-storage-modal__btn--confirm" id="attachmentStorageConfirmV2">Continue upload</button>
        </div>
    </div>
</div>

<!-- Duplicate email confirmation -->
<div class="duplicate-email-modal-overlay" id="duplicateEmailModalV2" aria-hidden="true">
    <div class="duplicate-email-modal" role="dialog" aria-labelledby="duplicateEmailModalTitleV2" aria-modal="true">
        <div class="duplicate-email-modal__icon" aria-hidden="true">
            @icon('envelope-open')
        </div>
        <h3 class="duplicate-email-modal__title" id="duplicateEmailModalTitleV2">Duplicate Email</h3>
        <p class="duplicate-email-modal__message">This email already exists.</p>
        <p class="duplicate-email-modal__filename" id="duplicateEmailFileNameV2"></p>
        <p class="duplicate-email-modal__question">Do you want to upload it anyway?</p>
        <div class="duplicate-email-modal__actions">
            <button type="button" class="duplicate-email-modal__btn duplicate-email-modal__btn--reject" id="duplicateEmailRejectV2">Reject</button>
            <button type="button" class="duplicate-email-modal__btn duplicate-email-modal__btn--accept" id="duplicateEmailAcceptV2">Accept</button>
        </div>
    </div>
</div>

<!-- Include necessary JavaScript -->
@once
@push('scripts')
@vite(['resources/js/pages/admin/emails-v2-entry.js'])
@endpush
@endonce

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Emails V2 interface loaded');
    
    // Debug: Check if elements exist
    const fileInput = document.getElementById('emailV2FileInput');
    const uploadArea = document.getElementById('upload-area-v2');
    const fileStatus = document.getElementById('fileStatusV2');
    
    console.log('File input found:', !!fileInput);
    console.log('Upload area found:', !!uploadArea);
    console.log('File status found:', !!fileStatus);
    
    // Debug: Check if modules are available (V2 module auto-initializes)
    console.log('loadEmailsV2 available:', typeof window.loadEmailsV2);
    
    // Load emails on page load
    if (typeof window.loadEmailsV2 === 'function') {
        console.log('Loading initial emails V2...');
        setTimeout(function() {
            window.loadEmailsV2();
        }, 100);
    } else {
        console.log('Auto-loading emails from main module');
    }
});
</script>
