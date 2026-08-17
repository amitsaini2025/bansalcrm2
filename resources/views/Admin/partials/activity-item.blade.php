{{-- Single activity log item (shared by detail pages and AJAX refresh) --}}
<div class="activity" id="activity_{{ $activit->id }}">
	<div class="activity-icon bg-primary text-white">
		<span>{{ substr($admin->first_name, 0, 1) }}</span>
	</div>
	<div class="activity-detail">
		<div class="activity-head">
			<div class="activity-title">
				<p><b>{{ $admin->first_name }}</b> {{ $activit->subject }}</p>
			</div>

			<div class="activity-head-actions">
				<div class="activity-date">
					<span class="text-job">{{ date('d M Y, H:i A', strtotime($activit->created_at)) }}</span>
				</div>

				<div class="activity-actions">
					@if($activit->pin == 1)
						<div class="pined_note">@icon('thumbtack', 'solid', ['attrs' => ['style' => 'font-size: 12px;color: #6777ef;']])</div>
					@endif

					<div class="dropdown d-inline dropdown_ellipsis_icon">
						<a class="dropdown-toggle" href="javascript:;" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">@icon('ellipsis-v')</a>
						<div class="dropdown-menu">
							@if(Auth::user()->role == 1)
								<a data-id="{{ $activit->id }}" data-href="deleteactivitylog" class="dropdown-item deleteactivitylog" href="javascript:;">Delete</a>
							@endif
							@if($activit->pin == 1)
								<a data-id="{{ $activit->id }}" class="dropdown-item pinactivitylog" href="javascript:;">UnPin</a>
							@else
								<a data-id="{{ $activit->id }}" class="dropdown-item pinactivitylog" href="javascript:;">Pin</a>
							@endif
						</div>
					</div>
				</div>
			</div>
		</div>

		@if(!empty($activit->description))
			@php
				$description = $activit->description;
				$subject = strtolower($activit->subject ?? '');
				$actType = $activit->activity_type ?? '';

				$inferredType = 'other';
				if (in_array($actType, ['receipt_created','receipt_validated','receipt_edited','receipt_reassigned','receipt_refunded','receipt_voided']) || strpos($description, 'action: receipt_') === 0) {
					$inferredType = 'receipt';
				} elseif ($actType === 'document' || preg_match('/\b(document|uploaded|verified|attached|detached)\b/i', $subject)) {
					$inferredType = 'document';
				} elseif ($actType === 'sms' || preg_match('/\b(sms|message sent|text)\b/i', $subject)) {
					$inferredType = 'message';
				} elseif (preg_match('/\b(added a note|updated a note|deleted a note)\b/i', $subject)) {
					$inferredType = 'note';
				} elseif (preg_match('/\b(sent a message)\b/i', $subject)) {
					$inferredType = 'message';
				} elseif (preg_match('/\b(call not picked|call)\b/i', $subject) || preg_match('/\bCall not picked\b/', $description)) {
					$inferredType = 'call';
				} elseif (preg_match('/\b(review|rated|rating)\b/i', $subject)) {
					$inferredType = 'review';
				} elseif (preg_match('/\b(reminder|checklist email|checklist resent|document checklist)\b/i', $subject)) {
					$inferredType = 'reminder';
				} elseif (preg_match('/\b(action|task|completed action|marked action)\b/i', $subject) || $activit->task_status == 1) {
					$inferredType = 'action';
				} elseif (preg_match('/\b(receipt|invoice|payment)\b/i', $subject)) {
					$inferredType = 'accounting';
				} elseif (preg_match('/\b(started an application)\b/i', $subject)) {
					$inferredType = 'application';
				} elseif (preg_match('/\b(interested service)\b/i', $subject)) {
					$inferredType = 'service';
				} elseif (preg_match('/\b(status|rated|rating)\b/i', $subject)) {
					$inferredType = 'status';
				} elseif (preg_match('/\b(check-in|session|commented|sheet comment)\b/i', $subject)) {
					$inferredType = 'checkin';
				}

				$isReceiptActivity = in_array($actType, ['receipt_created','receipt_validated','receipt_edited','receipt_reassigned','receipt_refunded','receipt_voided']) || (strpos($description, 'action: receipt_') === 0);
				$receiptFields = [];
				$parsedAction = $actType;
				if ($isReceiptActivity) {
					$lines = preg_split('/\r?\n/', trim($description));
					if (empty($parsedAction) && preg_match('/^action:\s*(\S+)/m', $description, $am)) {
						$parsedAction = $am[1];
					}
					$displayLabels = [
						'receipt_id' => 'Receipt ID', 'trans_no' => 'Trans. No', 'trans_date' => 'Trans. Date', 'entry_date' => 'Entry Date',
						'payment_method' => 'Payment Method', 'description' => 'Description', 'deposit_amount' => 'Amount', 'application_name' => 'Application',
						'document_attached' => 'Document', 'parent_receipt_id' => 'Parent Receipt', 'refund_reason' => 'Refund Reason', 'reassignment_reason' => 'Reassignment Reason',
					];
					foreach ($lines as $line) {
						if (preg_match('/^(\w[\w_]+):\s*(.+)$/', trim($line), $m)) {
							$key = $m[1];
							$value = trim($m[2]);
							if (in_array($key, ['action', 'performed_at', 'performed_by', 'deposit_amount'])) continue;
							$label = $displayLabels[$key] ?? ucfirst(str_replace('_', ' ', $key));
							$receiptFields[] = ['label' => $label, 'value' => $value];
						}
					}
				}

				$skipXml = (strpos($description, '<xml>') !== false || strpos($description, '<o:OfficeDocumentSettings>') !== false);
			@endphp

			@if($isReceiptActivity && count($receiptFields) > 0)
				<div class="activity-content-card">
					<div class="activity-type-badge activity-type-badge--{{ str_replace('receipt_', '', $parsedAction ?: 'created') }}">
						{{ ucfirst(str_replace(['receipt_', '_'], ['', ' '], $parsedAction ?: 'receipt')) }}
					</div>
					<div class="activity-receipt-grid">
						@foreach($receiptFields as $field)
							<div class="activity-receipt-field {{ ($field['label'] ?? '') === 'Amount' ? 'activity-receipt-field--amount' : '' }}">
								<span class="activity-receipt-label">{{ $field['label'] }}</span>
								<span class="activity-receipt-value">{{ $field['value'] }}</span>
							</div>
						@endforeach
					</div>
				</div>
			@elseif($skipXml)
				<div class="activity-content-card">
					<div class="activity-type-badge activity-type-badge--other">Note</div>
					<div class="activity-content-body"><p>{!! htmlentities($description) !!}</p></div>
				</div>
			@else
				<div class="activity-content-card">
					<div class="activity-type-badge activity-type-badge--{{ $inferredType }}">{{ ucfirst(str_replace('_', ' ', $inferredType)) }}</div>
					<div class="activity-content-body">{!! \App\Helpers\Helper::normalizeActivityDescriptionHtml($description, $inferredType === 'note') !!}</div>
				</div>
			@endif
		@endif

		@if(isset($activit->task_status) && $activit->task_status == '1')
			<p style="color:#4caf50;"><b>Completed</b></p>
		@endif

		@if(!empty($activit->followup_date))
			<p>{!! $activit->followup_date !!}</p>
		@endif

		@if(!empty($activit->task_group))
			<p>{!! $activit->task_group !!}</p>
		@endif
	</div>
</div>
