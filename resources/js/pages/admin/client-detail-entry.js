/**
 * Client detail page — Vite entry (Phase 2d).
 * Requires window.AppConfig / PageConfig from detail.blade.php inline script first.
 */
'use strict';

import '@legacy/common/config.js';
import '@legacy/common/ajax-helpers.js';
import '@legacy/common/crud-operations.js';
import '@legacy/common/activity-handlers.js';
import '@legacy/common/document-handlers.js';
import '@legacy/pages/admin/client-detail/preview-file-scroll.js';
import '@legacy/common/ui-components.js';
import '@legacy/common/modal-utils.js';

import '@legacy/pages/admin/client-detail/session-handlers.js';
import '@legacy/pages/admin/client-detail/assignee-handlers.js';
import '@legacy/pages/admin/client-detail/email-handlers.js';
import '@legacy/pages/admin/client-detail/document-upload.js';
import '@legacy/pages/admin/client-detail/document-rename.js';
import '@legacy/pages/admin/client-detail/document-actions.js';
import '@legacy/pages/admin/client-detail/document-categories.js';
import '@legacy/pages/admin/client-detail/drag-drop-handlers.js';
import '@legacy/pages/admin/client-detail/datatable-handlers.js';
import '@legacy/pages/admin/client-detail/application-handlers.js';
import '@legacy/pages/admin/client-detail/application-stage.js';
import '@legacy/pages/admin/client-detail/commission-handlers.js';
import '@legacy/pages/admin/client-detail/download-and-chatgpt.js';
import '@legacy/pages/admin/client-detail/document-context-menu.js';
import '@legacy/pages/admin/client-detail/document-signature.js';
import '@legacy/pages/admin/client-detail/ui-layout-and-tabs.js';
import '@legacy/pages/admin/client-detail/communications.js';
import '@legacy/pages/admin/client-detail/ui-initialization.js';
import '@legacy/pages/admin/client-detail/receipts-and-payments.js';
import '@legacy/pages/admin/client-detail/modal-handlers.js';
import '@legacy/pages/admin/client-detail/assignments.js';
import '@legacy/pages/admin/client-detail/schedule-followup.js';
import '@legacy/pages/admin/client-detail/delete-handlers.js';
import '@legacy/pages/admin/client-detail/pin-and-publish.js';
import '@legacy/pages/admin/client-detail/notes.js';

import '@legacy/pages/admin/client-detail.js';
import '@legacy/pages/admin/client-detail/blade-inline.js';

import '@legacy/popover.js';
