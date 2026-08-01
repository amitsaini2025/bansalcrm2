<?php
$productdetail = \App\Models\Product::where('id', $fetchData->product_id)->first();
$partnerdetail = \App\Models\Partner::where('id', $fetchData->partner_id)->first();
$PartnerBranch = \App\Models\PartnerBranch::where('id', $fetchData->branch)->first();
$workflow = \App\Models\Workflow::where('id', $fetchData->workflow)->first();
$branchEmail = ($PartnerBranch && !empty($PartnerBranch->email)) ? trim((string) $PartnerBranch->email) : '';
$partnerPrimaryEmail = ($partnerdetail && !empty($partnerdetail->email)) ? trim((string) $partnerdetail->email) : '';
$partnerAltEmail = \App\Models\PartnerEmail::where('partner_id', $fetchData->partner_id)->orderBy('id')->value('partner_email');
$partnerAltEmail = $partnerAltEmail ? trim((string) $partnerAltEmail) : '';
$collegeRecipientEmail = $branchEmail !== '' ? $branchEmail : ($partnerPrimaryEmail !== '' ? $partnerPrimaryEmail : $partnerAltEmail);
$collegeRecipientName = $partnerdetail->partner_name ?? 'College';
?>
<style>
.checklist .round{background: #fff;border: 1px solid #000; border-radius: 50%;font-size: 10px;line-height: 14px; padding: 2px 5px;width: 16px; height: 16px; display: inline-block;}
.circular-box {height: 24px;width: 24px;line-height: 24px;display: inline-block; text-align: center; box-shadow: 0 4px 6px 0 rgb(34 36 38 / 12%), 0 2px 12px 0 rgb(34 36 38 / 15%);}
.circular-box{background: #fff;border: 1px solid #d2d2d2;border-radius: 50%;}
.transparent-button { background-color: transparent;border: none;cursor: pointer;}
.checklist span.check, .mychecklistdocdata span.check{background: #71cc53;color: #fff;border-radius: 50%;font-size: 10px;line-height: 14px;padding: 2px 3px;width: 18px;height: 18px;display: inline-block;}
</style>
<div class="card-header-action" style="padding-bottom:15px;">
	<div class="float-start">
		<h5 class="applicationstatus">
            <?php
            if($fetchData->status == 0){ ?>In Progress<?php }
            else if($fetchData->status == 1){ echo 'Completed'; }
            else if($fetchData->status == 2){ echo 'Discontinued'; }
            else if($fetchData->status == 3){ echo 'Cancelled'; }
            else if($fetchData->status == 4){ echo 'Withdrawn'; }
            else if($fetchData->status == 5){ echo 'Deferred'; }
            else if($fetchData->status == 6){ echo 'Future'; }
            else if($fetchData->status == 7){ echo 'VOE'; }
            else if($fetchData->status == 8){ echo 'Refund'; }
            ?>
        </h5>
	</div>
	<div class="float-end">
		<div class="application_btns">
			@if($collegeRecipientEmail !== '')
			<button type="button" class="btn btn-outline-secondary btn-sm application-compose-email me-1 mb-1"
				title="Compose to school / college on this application"
				data-application-id="{{ (int) $fetchData->id }}"
				data-college-email="{{ e($collegeRecipientEmail) }}"
				data-college-name="{{ e($collegeRecipientName) }}">
				@icon('envelope') College
			</button>
			@endif
			<a target="_blank" href="{{URL::to('/application/export/pdf/')}}/{{$fetchData->id}}" class="btn btn-primary">@icon('print')</a>
          
			<a style="<?php if($fetchData->status == 2 || $fetchData->status == 1){ echo 'display:none;'; } ?>" href="javascript:;" data-id="{{$fetchData->id}}" class="btn btn-outline-danger discon_application ifdiscont">@icon('times') Discontinue</a>

            <a style="<?php if($fetchData->status == 2 || $fetchData->status == 1 || $fetchData->status == 8){ echo 'display:none;'; } ?>" href="javascript:;" data-id="{{$fetchData->id}}" class="btn btn-outline-danger refund_application ifdiscont">@icon('undo') Refund</a>

			<?php
			$displayback = false;
			$workflowstage = \App\Models\WorkflowStage::where('w_id', $fetchData->workflow)->orderBy('id','desc')->first();
		
			if($workflowstage->name == $fetchData->stage){
				$displayback = true;
			} 
			?>
			<a href="javascript:;" style="<?php if($fetchData->status == 2 || $fetchData->status == 1 || $fetchData->status == 8){ echo 'display:none;'; } ?>" data-stage="{{$fetchData->stage}}" data-id="{{$fetchData->id}}" class="btn btn-outline-primary backstage ifdiscont">@icon('angle-left') Back to Previous Stage</a>

			<a href="javascript:;" style="<?php if($fetchData->status == 2){ echo 'display:none;'; } ?> <?php if($displayback){  }else{ echo 'display:none;'; } ?>" data-stage="{{$fetchData->stage}}" data-id="{{$fetchData->id}}" class="btn btn-success completestage ifdiscont">Complete Application</a>
			<a href="javascript:;" style="<?php if($displayback || $fetchData->status == 2 || $fetchData->status == 8){ echo 'display:none;'; } ?>" data-stage="{{$fetchData->stage}}" data-id="{{$fetchData->id}}" class="btn btn-success nextstage ifdiscont">Proceed to Next Stage @icon('angle-right')</a>

			<a href="javascript:;" style="<?php if($fetchData->status == 1 || $fetchData->status == 2){ echo ''; }else{ echo 'display:none;'; } ?>"  data-id="{{$fetchData->id}}" class="btn btn-success revertapp">Revert @icon('angle-right')</a>

			<span id="discontinue_reason" style="<?php if($fetchData->status == 2){ echo 'display:block;'; } else { echo 'display:none;'; } ?>">Discontinue Reason - <span id="discontinue_reason_text"><?php echo $fetchData->discontinue_reason; ?></span></span>

            <span id="discontinue_note" style="<?php if($fetchData->status == 2){ echo 'display:block;'; } else { echo 'display:none;'; } ?>">Discontinue Note - <span id="discontinue_note_text"><?php echo $fetchData->discontinue_note; ?></span></span>

            <span id="refund_note" style="<?php if($fetchData->status == 8){ echo 'display:block;'; } else { echo 'display:none;'; } ?>">Refund Note - <span id="refund_note_text"><?php echo $fetchData->refund_notes; ?></span></span>


		</div>
	</div>
</div>
<div class="clearfix"></div>
<div class="application_grid_col">
	<div class="grid_column">
		<span>Course:</span>
		<p>{{@$productdetail->name}}</p>
	</div>
	<div class="grid_column">
		<span>School:</span>
		<p>{{@$partnerdetail->partner_name}}</p>
	</div>
	<div class="grid_column">
		<span>Branch:</span>
		<p>{{@$PartnerBranch->name}}</p>
	</div>
	<div class="grid_column">
		<span>Workflow:</span>
		<p>{{@$workflow->name}}</p>
	</div>
	<div class="grid_column">
		<span>Current Stage:</span>
		<p class="text-success curerentstage" style="font-weight: 600;font-size: 14px;">{{@$fetchData->stage}}</p>
	</div>
</div>
<div class="application_grid_col application_grid_col_secondary">
	<div class="grid_column">
		<span>Application Id:</span>
		<p>{{$fetchData->id}}</p>
	</div>
	<!--<div class="grid_column">
		<span>Partner's Client Id:</span>
		<p>22</p>
	</div>-->
	<div class="grid_column">
		<span>Started at:</span>
		<p>{{date('Y-m-d', strtotime($fetchData->created_at))}}</p>
	</div>
	<div class="grid_column">
		<span>Last Updated:</span>
		<p>{{date('Y-m-d', strtotime($fetchData->updated_at))}}</p>
	</div>
	<div class="grid_column">
		<span>Enrolment Type:</span>
		<p class="mb-0">
			{!! \App\Models\Application::enrolmentTypeSelectHtml(
				(int) $fetchData->id,
				$fetchData->enrolment_type ?? null,
				'form-control form-control-sm application-enrolment-type-field',
				! \App\Models\Application::canEditEnrolmentOrCompanyValue($fetchData->enrolment_type ?? null)
			) !!}
		</p>
	</div>
	<div class="grid_column">
		<span>Company Name:</span>
		<p class="mb-0">
			{!! \App\Models\Application::companyNameSelectHtml(
				(int) $fetchData->id,
				$fetchData->company_name ?? null,
				'form-control form-control-sm application-company-name-field',
				! \App\Models\Application::canEditEnrolmentOrCompanyValue($fetchData->company_name ?? null)
			) !!}
		</p>
	</div>
	<div class="grid_column">
		<div class="overall_progress">
			<span>Overall Progress:</span>
			<?php
			if($fetchData->progresswidth == 0){
				$width = 0;
			}else{
				$width = $fetchData->progresswidth;
			}
			
			$over = '';
			if($width > 50){
				$over = '50';
			}
			?>
			 <div id="progresscir" class="progress-circle over_{{$over}} prgs_{{$width}}">
			   <span>{{$width}} %</span>
			   <div class="left-half-clipper"> 
				  <div class="first50-bar"></div>
				  <div class="value-bar"></div>
			   </div>
			</div> 
		</div>
	</div>
</div>
<div class="clearfix"></div>
<div class="divider"></div>
<div class="row">
	<div class="col-md-9">
		<div class="application_other_info">
			<ul class="nav nav-pills" id="applicat_detail_tabs" role="tablist">
				<li class="nav-item">
					<a class="nav-link active" id="applicate_activities-tab" data-bs-toggle="tab" href="#applicate_activities">Activities</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" id="documents-tab" data-bs-toggle="tab" href="#documents">Documents</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" id="notes-tab" data-id="{{$fetchData->id}}" data-bs-toggle="tab"  href="#notes">Notes</a>
				</li>
				<!--<li class="nav-item">
					<a class="nav-link" id="tasks-tab" data-bs-toggle="tab" href="#tasks">Tasks</a>
				</li> -->
				<!-- NOTE: Payment Schedule tab removed - Invoice Schedule feature has been removed -->
			</ul> 
			<div class="tab-content" id="applicationContent">
				<div class="tab-pane fade show active" id="applicate_activities" role="tabpanel" aria-labelledby="applicate_activities-tab">
					<?php
					$sheetCommentLog = \App\Models\ApplicationActivitiesLog::where('app_id', $fetchData->id)->where('type', 'sheet_comment')->orderBy('updated_at', 'desc')->first();
					?>
					<div class="mb-3 d-flex align-items-center gap-2">
						<label class="mb-0">Filter:</label>
						<select id="activity_filter_type" class="form-control form-control-sm" style="width: auto;">
							<option value="all">All activities</option>
							<option value="sheet_comment">Sheet comments only</option>
						</select>
					</div>
					<div id="activities_sheet_comment_section" class="activities-sheet-comment-list mb-3">
						<div class="accordion cus_accrodian">
							<div class="accordion-header app_green" role="button" data-bs-toggle="collapse" data-bs-target="#sheet_comment_accor" aria-expanded="true">
								<h4>Sheet comment</h4>
							</div>
							<div class="accordion-body collapse show" id="sheet_comment_accor">
								<div class="activity_list">
									<?php if ($sheetCommentLog) {
										$admin = \App\Models\Staff::find($sheetCommentLog->user_id);
									?>
									<div class="activity_col">
										<div class="activity_txt_time">
											<span class="span_txt"><b><?php echo $admin ? $admin->first_name : ''; ?></b> <?php echo e($sheetCommentLog->comment); ?></span>
											<span class="span_time"><?php echo date('d D, M Y h:i A', strtotime($sheetCommentLog->updated_at)); ?></span>
										</div>
										<?php if (!empty($sheetCommentLog->title)) { ?>
										<div class="app_description">
											<div class="app_card">
												<div class="app_title"><?php echo e($sheetCommentLog->title); ?></div>
											</div>
										</div>
										<?php } ?>
									</div>
									<?php } else { ?>
									<div class="activity_col text-muted">No sheet comment for this application.</div>
									<?php } ?>
								</div>
							</div>
						</div>
					</div>
					<div id="accordion" class="activities-stage-list">
					<?php
			
						
						if($fetchData->status == 1){
							$stage9 = 'app_green';
						}
						$stagesquery = \App\Models\WorkflowStage::where('w_id', $fetchData->workflow)->get();
							
		
						foreach($stagesquery as $stages){
								$stage1 = '';
					?>
					<?php
					$workflowstagess = \App\Models\WorkflowStage::where('name', $fetchData->stage)->where('w_id', $fetchData->workflow)->first();
					$stagearray = array();
					if($workflowstagess){
					$prevdata = \App\Models\WorkflowStage::where('id', '<', @$workflowstagess->id)->where('w_id', $fetchData->workflow)->orderBy('id','Desc')->get();
					
					foreach($prevdata as $pre){
						$stagearray[] = $pre->id;
					}
					}
					
							
							if(in_array($stages->id, $stagearray)){
								$stage1 = 'app_green';
							}
							if($fetchData->status == 1){
								$stage1 = 'app_green';
							}
							$stagname = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $stages->name)));
							?>
						<div class="accordion cus_accrodian">
							
							<div class="accordion-header collapsed <?php echo $stage1; ?> <?php if($fetchData->stage == $stages->name && $fetchData->status != 1){ echo  'app_blue'; }  ?>">
								<h4 class="stage-collapse-toggle" role="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $stagname; ?>_accor" aria-expanded="false" tabindex="0"><?php echo $stages->name; ?></h4>
								<div class="accord_hover">
									<a title="Add Note" class="openappnote" data-app-type="<?php echo $stages->name; ?>" data-id="<?php echo $fetchData->id; ?>" href="javascript:;">@icon('file-alt')</a>
									<a title="Add Document" class="opendocnote" data-app-type="<?php echo $stagname; ?>" data-typename="<?php echo $stages->name; ?>" data-id="<?php echo $fetchData->id; ?>" data-appdocclientid="<?php echo $fetchData->client_id;?>" href="javascript:;">@icon('file-image')</a>
                                  
                                   <a data-course="<?php echo $productdetail->name; ?>" data-school="<?php echo $partnerdetail->partner_name; ?>" data-app-type="<?php echo $stages->name; ?>" title="Actions" class="openappaction" data-id="<?php echo $fetchData->id; ?>" href="javascript:;">@icon('calendar')</a>
                                    
									<!--<a data-app-type="<?php //echo $stages->name; ?>" title="Add Appointments" class="openappappoint" data-id="<?php //echo $fetchData->id; ?>" href="javascript:;">@icon('calendar')</a>-->
                                  
									<a data-app-type="<?php echo $stages->name; ?>" title="Email" data-id="{{@$fetchData->id}}" data-email="{{@$fetchedData->email}}" data-name="{{@$fetchedData->first_name}} {{@$fetchedData->last_name}}" class="openclientemail" title="Compose Mail" href="javascript:;">@icon('envelope')</a>
								</div>
							</div>
							<?php
							$applicationlists = \App\Models\ApplicationActivitiesLog::where('app_id', $fetchData->id)->where('stage',$stages->name)->orderby('created_at', 'DESC')->get();
							
							?>
							<div class="accordion-body collapse" id="<?php echo $stagname; ?>_accor" data-parent="#accordion" style="">
								<div class="activity_list">
								<?php foreach($applicationlists as $applicationlist){ 
								$admin = \App\Models\Staff::find($applicationlist->user_id);
								?>
									<div class="activity_col">
										<div class="activity_txt_time">
											<span class="span_txt"><b>{{$admin->first_name}}</b> {!! $applicationlist->comment !!}</span>
											<span class="span_time"><?php echo date('d D, M Y h:i A', strtotime($applicationlist->created_at)); ?></span>
										</div>
										<?php if($applicationlist->title != ''){ ?>
										<div class="app_description"> 
											<div class="app_card">
												<div class="app_title"><?php echo $applicationlist->title; ?></div>
											</div>
											<?php if($applicationlist->description != ''){ ?>
											<div class="log_desc">
												<?php echo $applicationlist->description; ?>
											</div>
											<?php } ?>
										</div>	
										<?php } ?> 
									</div>
								<?php } ?>
								</div>
							</div>
						</div>
						<?php } ?>
						
					</div> 
				</div> 
				<div class="tab-pane fade" id="documents" role="tabpanel" aria-labelledby="documents-tab">
					<div class="document_checklist">
					<?php
					$applicationdocumentco = \App\Models\ApplicationDocumentList::where('application_id', $fetchData->id)->count();
					$application_id =  $fetchData->id;
					$applicationuploadcount = DB::select("SELECT COUNT(DISTINCT list_id) AS cnt FROM application_documents where application_id = '$application_id'");
					$stagesquery = \App\Models\WorkflowStage::where('w_id', $fetchData->workflow)->get();
					?>
						<h4>Document Checklist (<span class="checklistuploadcount">{{@$applicationuploadcount[0]->cnt}}</span>/<span class="checklistcount">{{@$applicationdocumentco}}</span>)</h4>
						<p>The changes & addition of the checklist will only be affected to current application only.</p>
						<div class="row">
							<div class="col-md-5">
								<div class="checklist">
										<ul>
										<?php
										foreach($stagesquery as $stages){
											$name = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $stages->name)));
											
										?>
											<li><span><?php echo $stages->name; ?></span>
											<div class="<?php echo $name; ?>_checklists">
												<?php
												$applicationdocumentsquery = \App\Models\ApplicationDocumentList::where('application_id', $fetchData->id)->where('type', $name);
												$applicationdocumentscount = $applicationdocumentsquery->count();
												$applicationdocuments = $applicationdocumentsquery->get();
												if($applicationdocumentscount !== 0){
													
												?>
												<table class="table">
													<tbody>
														<?php foreach($applicationdocuments as $applicationdocument){ 
														$appcount = \App\Models\ApplicationDocument::where('list_id', $applicationdocument->id)->count();
														?>
														<tr>
															<td><?php if($appcount >0){ ?><span class="check">@icon('check')</span><?php }else{ ?><span class="round"></span><?php } ?></td>
															<td>{{@$applicationdocument->document_type}}</td>
															<td><div class="circular-box cursor-pointer"><button class="transparent-button paddingNone">{{@$appcount}}</button></div></td>
															<td><a data-aid="{{$fetchData->id}}" data-typename="<?php echo $stages->name; ?>" data-type="<?php echo $name; ?>" data-id="{{$applicationdocument->id}}" class="openfileupload" href="javascript:;">@icon('plus')</a></td>
														</tr>
														<?php } ?>
													</tbody>
													
												</table>
												<?php } ?>
											</div>
											<a class="openchecklist" data-id="{{$fetchData->id}}" data-typename="<?php echo $stages->name; ?>" data-type="<?php echo $name; ?>" href="javascript:;">@icon('plus') Add New Checklist</a></li>
										<?php } ?>
											
										</ul> 
						</div>
							</div>
							<div class="col-md-7">
								<div class="table-responsive"> 
						<table class="table text_wrap">
							<thead>
								<tr>
									<th>Filename / Checklist</th>
									<th>Related Stage</th>
									<th>Added By</th>
									<th>Added On</th>
									<th></th>
								</tr> 
							</thead>
							<tbody class="tdata mychecklistdocdata">	
							<?php
							$doclists = \App\Models\ApplicationDocument::where('application_id',$fetchData->id)->orderby('created_at','DESC')->get();
							foreach($doclists as $doclist){
								$docdata = \App\Models\ApplicationDocumentList::where('id', $doclist->list_id)->first();
							?>
								<tr>
									<td>@icon('file') <?php echo $doclist->file_name; ?><br><?php echo @$docdata->document_type; ?></td>
									<td>
										<?php
										echo $doclist->typename;
										?>
									</td>
									<td><?php
									$admin = \App\Models\Staff::find($doclist->user_id);
									?><span style="position: relative;background: rgb(3, 169, 244);font-size: .8rem;height: 24px;line-height: 24px;min-width: 24px;width: 24px;color: #fff;display: block;font-weight: 600;letter-spacing: 1px;text-align: center;border-radius: 50%;overflow: hidden;"><?php echo substr($admin->first_name, 0, 1); ?></span><?php echo $admin->first_name; ?></td>
									<td><?php echo date('Y-m-d',strtotime($doclist->created_at)); ?></td>
									<td>
										<?php if($doclist->status == 1){
											?>
											<span class="check">@icon('eye')</span>
											<?php
										} ?>
										<div class="dropdown d-inline">
											<button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button>
											<div class="dropdown-menu">
												
												<!--<a target="_blank" class="dropdown-item" href="{{--URL::to('/public/img/documents')--}}/<?php //echo $doclist->file_name; ?>">Preview</a>
												<a data-id="{{--$doclist->id--}}" class="dropdown-item deletenote" data-href="deleteapplicationdocs" href="javascript:;">Delete</a>
												<a download class="dropdown-item" href="{{--URL::to('/public/img/documents')--}}/<?php //echo $doclist->file_name; ?>">Download</a>-->

                                                <a target="_blank" class="dropdown-item" href="<?php echo $doclist->myfile; ?>">Preview</a>
                                                <a data-id="{{$doclist->id}}" class="dropdown-item deletenote" data-href="deleteapplicationdocs" href="javascript:;">Delete</a>
                                                <a download class="dropdown-item" href="<?php echo $doclist->myfile; ?>">Download</a>

												<?php
												if($doclist->status == 0){
												?>
												<a data-id="{{$doclist->id}}" href="javascript:;" class="dropdown-item publishdoc"  >Publish Document</a>
												<?php }else{ ?>
												<a data-id="{{$doclist->id}}" href="javascript:;" class="dropdown-item unpublishdoc"  >Unpublish Document</a>
												<?php } ?>
											</div>
										</div>								  
									</td>
								</tr>
							<?php } ?>
							</tbody>
							<!--<tbody>
								<tr>
									<td style="text-align:center;" colspan="10">
										No Record found
									</td>
								</tr>
							</tbody>-->
						</table> 
					</div>
							</div>
						</div>
						
					</div>
					
				</div>
				<div class="tab-pane fade" id="notes" role="tabpanel" aria-labelledby="notes-tab">
				</div>
				{{-- Task system removed - December 2025 --}}
				<!--<div class="tab-pane fade" id="tasks" role="tabpanel" aria-labelledby="tasks-tab">
					<div id="taskaccordion">
					<?php
					$stagesquery = \App\Models\WorkflowStage::where('w_id', $fetchData->workflow)->get();
					foreach($stagesquery as $stages){
						$stagname = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $stages->name)));
					?>
						<div class="accordion cus_accrodian">
							<div class="accordion-header collapsed active" role="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $stagname; ?>_accor" aria-expanded="false">
								<h4><?php echo $stages->name; ?></h4>
								<div class="accord_hover">
									<a title="Add Task" class="opentaskmodal" href="javascript:;">@icon('suitcase')</a>
								</div>
							</div>
						</div>
					<?php } ?>
					</div>
				</div>-->
				<!-- NOTE: Payment Schedule tab content removed - Invoice Schedule feature has been removed --> 
			</div>
		</div>
	</div>
	<div class="col-md-3">
		<div class="cus_sidebar">
			<div class="form-group">
				<label for="applied_intake">Applied Intake:</label>
				{!! Form::text('applied_intake', $fetchData->intakedate, array('class' => 'form-control datepicker', 'data-valid'=>'', 'autocomplete'=>'off','placeholder'=>'Select Date' ))  !!}
				<span class="span_note">Date must be in YYYY-MM-DD (2012-12-22) format.</span>
				@if ($errors->has('applied_intake'))
					<span class="custom-error" role="alert">
						<strong>{{ @$errors->first('applied_intake') }}</strong>
					</span> 
				@endif 
			</div>
			
			<div class="app_date_sec">
				<div class="app_start_date common_apply_date">
					<span>Start</span>
					<div class="date_col">
						<div class="add_date">
							<span>@icon('plus') Add</span>
						</div>  
						<input type="text" value="{{@$fetchData->start_date}}" class="startdatepicker" />
						<div class="apply_val">
						
							<span class="month"><?php if(@$fetchData->start_date != ''){ echo date('M',strtotime($fetchData->start_date)); }else{ echo '-'; }?></span>
							<span class="day"><?php if(@$fetchData->start_date != ''){ echo date('d',strtotime($fetchData->start_date)); }else{ echo '-'; }?></span>
							<span class="year"><?php if(@$fetchData->start_date != ''){ echo date('Y',strtotime($fetchData->start_date)); }else{ echo '-'; }?></span>
						</div>
					</div>
				</div>
				<div class="app_end_date common_apply_date">
					<span>End</span>
					<div class="date_col">
						<div class="add_date">
							<span>@icon('plus') Add</span> 
						</div>
						<input type="text" value="{{@$fetchData->end_date}}" class="enddatepicker" />
						<div class="apply_val">
							<span class="month"><?php if(@$fetchData->end_date != ''){ echo date('M',strtotime($fetchData->end_date)); }else{ echo '-'; }?></span>
							<span class="day"><?php if(@$fetchData->end_date != ''){ echo date('d',strtotime($fetchData->end_date)); }else{ echo '-'; }?></span>
							<span class="year"><?php if(@$fetchData->end_date != ''){ echo date('Y',strtotime($fetchData->end_date)); }else{ echo '-'; }?></span>
						</div>
					</div>
				</div>
			</div>
			<div class="clearfix"></div>
			<div class="divider"></div>
			<div class="setup_payment_sche">
				<?php
			$appfeeoption = \App\Models\ApplicationFeeOption::where('app_id', $fetchData->id)->first(); //dd($appfeeoption);
			$totl = 0.00;
            $commission_tot = 0.00;
			$discount = 0.00;
			if($appfeeoption){
				$appfeeoptiontype = \App\Models\ApplicationFeeOptionType::where('fee_id', $appfeeoption->id)->get();
				foreach($appfeeoptiontype as $fee){
					$totl += $fee->total_fee;
                    $commission_tot += $fee->commission;
				}
			}
			$net = $totl - $discount;
			// NOTE: Invoice Schedule setup button removed - Invoice Schedule feature has been removed
			?>
			</div>
          
          	<div class="divider"></div>
            <input type="text" name="student_id" data-applicationid="{{$fetchData->id}}" id="student_id" value="{{$fetchData->student_id}}" placeholder="Enter Student Id" style="width: 132px;">
            
			<div class="divider"></div>
			
			<div class="cus_prod_fees">
				<h5>Product Fees <span>AUD</span></h5>
				<a href="javascript:;" data-id="{{$fetchData->id}}" data-partnerid="{{$fetchData->partner_id}}" class="openpaymentfee">@icon('edit')</a>
				<div class="clearfix"></div>
			</div>
			<!--<p class="clearfix"> 
				<span class="float-start">Total Fee</span>
				<span class="float-end text-muted product_totalfee">{{--$totl--}}</span>
			</p>
            <p class="clearfix">
				<span class="float-start">Commission</span>
				<span class="float-end text-muted product_totalcommission">{{--$commission_tot--}}</span>
			</p>
			<p class="clearfix" style="color:#ff0000;"> 
				<span class="float-start">Discount</span>
				<span class="float-end text-muted product_discount">{{--$discount--}}</span>
			</p>
			<p class="clearfix" style="color:#6777ef;"> 
				<span class="float-start">Net Fee</span>
				<span class="float-end text-muted product_net_fee">{{--$net--}}</span>
			</p>-->
          
            <p class="clearfix">
				<span class="float-start">Total Course Fee</span>
				<span class="float-end text-muted total_course_fee_amount"> 
                  <?php 
				if( isset($appfeeoption['total_course_fee_amount']) &&  $appfeeoption['total_course_fee_amount'] != ''){
				echo $appfeeoption['total_course_fee_amount'];
			} else { echo "0.00";} ?></span>
			</p>
          
             <p class="clearfix">
				<span class="float-start">Scholarship Fee</span>
				<span class="float-end text-muted scholarship_fee_amount">
                 <?php
				if( isset($appfeeoption['scholarship_fee_amount']) &&  $appfeeoption['scholarship_fee_amount'] != ''){
					echo $appfeeoption['scholarship_fee_amount'];
				} else { echo "0.00";} ?></span>
			</p>

            <p class="clearfix">
				<span class="float-start">Enrolment Fee</span>
				<span class="float-end text-muted enrolment_fee_amount"> 
                 <?php 
				if( isset($appfeeoption['enrolment_fee_amount']) &&  $appfeeoption['enrolment_fee_amount'] != ''){
					echo $appfeeoption['enrolment_fee_amount'];
				} else { echo "0.00";} ?></span>
			</p>

            <p class="clearfix">
				<span class="float-start">Material fees</span>
				<span class="float-end text-muted material_fees"><?php 
				if( isset($appfeeoption['material_fees']) &&  $appfeeoption['material_fees'] != ''){
					echo $appfeeoption['material_fees'];
				} else { echo "0.00";} ?></span>
			</p>

            <p class="clearfix">
				<span class="float-start">Tution Fee</span>
				<span class="float-end text-muted tution_fees"><?php 
				if( isset($appfeeoption['tution_fees']) &&  $appfeeoption['tution_fees'] != ''){
					echo $appfeeoption['tution_fees'];
				} else { echo "0.00";} ?></span>
			</p>

            <div class="divider"></div>
			<div class="cus_prod_fees">
				<h5>Commission Status <span>AUD</span></h5>
				<?php
			$client_revenue = '0.00';
			if($fetchData->client_revenue != ''){
				$client_revenue = $fetchData->client_revenue;
			}
			$partner_revenue = '0.00';
			if($fetchData->partner_revenue != ''){
				$partner_revenue = $fetchData->partner_revenue;
			}
			$discounts = '0.00';
			if($fetchData->discounts != ''){
				$discounts = $fetchData->discounts;
			}
			$nettotal = $client_revenue + $partner_revenue - $discounts;
			?>
				<a href="javascript:;"  data-id="{{$fetchData->id}}" class="openpaymentfeeLatest btn btn-primary btn-sm float-end">@icon('plus') Add Fee</a>
				<div class="clearfix"></div>
			</div>
          
          
            <?php
			// Calculate Total Fee Paid from actual fee rows (source of truth) - fixes mismatch with Other Fee Option popup
			$total_fee_paid_display = '0.00';
			if ($appfeeoption) {
				$total_fee_paid_sum = \App\Models\ApplicationFeeOptionType::where('fee_id', $appfeeoption->id)
					->where('fee_option_type', 2)
					->sum('total_fee');
				$total_fee_paid_display = number_format((float) $total_fee_paid_sum, 2, '.', '');
			}
			?>
            <p class="clearfix appsaleforcast">
				<span class="float-start">Total Fee Paid</span>
				<span class="float-end text-muted fee_reported_by_college"><?php echo $total_fee_paid_display; ?></span>
			</p>

			<!--<p class="clearfix appsaleforcast"> 
				<span class="float-start">Partner Revenue</span>
				<span class="float-end text-muted partner_revenue">{{--$partner_revenue--}}</span>
			</p>
			<p class="clearfix appsaleforcast"> 
				<span class="float-start">Client Revenue</span>
				<span class="float-end text-muted client_revenue">{{--$client_revenue--}}</span>
			</p>
			<p class="clearfix appsaleforcast" style="color:#ff0000;"> 
				<span class="float-start">Discount</span>
				<span class="float-end text-muted discounts">{{--$discounts--}}</span>
			</p>
			<p class="clearfix appsaleforcast" style="color:#6777ef;"> 
				<span class="float-start">Net Revenue</span>
				<span class="float-end text-muted netrevenue">{{--number_format($nettotal,2,'.','')--}}</span>
			</p>-->
          
			<div class="form-group">
				<label for="expect_win_date">Expected Win Date:</label>
				{!! Form::text('expect_win_date', $fetchData->expect_win_date, array('class' => 'form-control expectdatepicker', 'data-valid'=>'', 'autocomplete'=>'off','placeholder'=>'Select Date' ))  !!}
				<span class="span_note">Date must be in YYYY-MM-DD (2012-12-22) format.</span>
				@if ($errors->has('expect_win_date'))
					<span class="custom-error" role="alert">
						<strong>{{ @$errors->first('expect_win_date') }}</strong>
					</span> 
				@endif 
			</div>
			<?php
				$admin = \App\Models\Staff::find($fetchData->user_id);
			?>
			<div class="divider"></div>
			<div class="setup_payment_sche">
			<?php
			$ratio = '100';
			if($fetchData->ratio != ''){
				$ratio = $fetchData->ratio;
			}
			?>
				<a href="javascript:;" data-ration="{{$ratio}}" data-name="{{$admin->first_name}}" data-id="{{$fetchData->id}}" class="btn btn-primary application_ownership">View Appliation Ownership Ratio</a>
		</div>
		
		<div class="divider"></div> 
		<div class="client_assign client_info_tags" id="application_assignee_block">
				<span class="">Assignee:</span>
				<div class="client_info">
					<div class="cl_logo" id="application_assignee_initial">@if($admin){{ substr(trim($admin->first_name.' '.$admin->last_name), 0, 1) }}@endif</div>
					<div class="cl_name">
						<span class="name" id="application_assignee_name">@if($admin){{ trim($admin->first_name.' '.$admin->last_name) }}@endif</span>
						<span class="email" id="application_assignee_email">@if($admin){{ $admin->email }}@endif</span>
					</div>
					@if(isset($assignees))
					<a href="javascript:;" class="btn btn-outline-primary btn-sm ms-2 application-change-assignee" data-app-id="{{ $fetchData->id }}" data-assignee-id="{{ $fetchData->user_id ?? '' }}">Change</a>
					@endif
				</div>
			</div>
			<div class="divider"></div> 
			<p class="clearfix"> 
				<span class="float-start">Super Agent:</span>
				<span class="float-end text-muted">
					<a href="javascript:;" data-id="{{$fetchData->id}}" class="btn btn-primary btn-sm opensuperagent">@icon('plus') Add</a>
					<?php
					$agent = \App\Models\Agent::where('id',$fetchData->super_agent)->first();
					if($agent){
					?>
					<div class="supagent_data">
						<div class="client_info">
							<div class="cl_logo" style="display: inline-block;width: 30px;height: 30px; border-radius: 50%;background: #6777ef;text-align: center;color: #fff;font-size: 14px; line-height: 30px; vertical-align: top;"><?php echo substr($agent->full_name, 0, 1); ?></div>
							<div class="cl_name" style="display: inline-block;margin-left: 5px;width: calc(100% - 60px);">
								<span class="name"><?php echo $agent->full_name; ?></span>
								<span class="badge bg-warning text-dark zippyLabel alignMiddle">
							  <?php echo $agent->struture; ?>
							</span>
							</div>
							<div class="cl_del" style="display: inline-block;">
								<a href="javascript:;" data-href="superagent" data-id="{{$fetchData->id}}"  class="deletenote">@icon('times')</a>
							</div>
						</div>
					</div>
					<?php } ?>
				</span>
			</p>
			<p class="clearfix"> 
				<span class="float-start">Sub Agent:</span>
				<span class="float-end text-muted">
					<a href="javascript:;" data-id="{{$fetchData->id}}" class="btn btn-primary btn-sm opensubagent">@icon('plus') Add</a>
					<div class="subagent_data">
						<?php
					$subagent = \App\Models\Agent::where('id',$fetchData->sub_agent)->first();
					if($subagent){
					?>
					<div class="client_info">
							<div class="cl_logo" style="display: inline-block;width: 30px;height: 30px; border-radius: 50%;background: #6777ef;text-align: center;color: #fff;font-size: 14px; line-height: 30px; vertical-align: top;"><?php echo substr($subagent->full_name, 0, 1); ?></div>
							<div class="cl_name" style="display: inline-block;margin-left: 5px;width: calc(100% - 60px);">
								<span class="name"><?php echo $subagent->full_name; ?></span>
								<span class="badge bg-warning text-dark zippyLabel alignMiddle">
							  <?php echo $subagent->struture; ?>
							</span>
							</div>
							<div class="cl_del" style="display: inline-block;">
								<a href="javascript:;" data-href="subagent" data-id="{{$fetchData->id}}"  class="deletenote">@icon('times')</a>
							</div>
						</div>
					<?php } ?>
					</div>
				</span>
			</p>
		</div> 
	</div>
</div>
<div class="feetypecopy" style="display:none;">
	<div class="fee_fields_row editfee_fields_row">
		<div class="field_col wd40">
			<select data-valid="required" class="form-control fee_type selectfee_type" id="fee_type" name="fee_type[]">
				<option value="">Select Fee Type</option>
				<option  value="Accommodation Fee">Accommodation Fee</option>
				<option  value="Administration Fee">Administration Fee</option>
				<option  value="Airline Ticket">Airline Ticket</option>
				<option > value="Airport Transfer Fee">Airport Transfer Fee</option>
				<option  value="Application Fee">Application Fee</option>
				<option  value="Tution Fee">Tution Fee</option>
			</select>
		</div>
		<div class="field_col wd25">
			<input data-valid="required" type="number" class="form-control fee_amount" name="fee_amount[]" value="" placeholder="0.00" />
		</div>
		<div class="field_col wd25">
			<input type="number" class="form-control comm_amount" name="comm_amount[]" value="" placeholder="0.00" />
		</div>
		<div class="field_col wd10">
			<a href="javascript:;" class="payremoveitems">@icon('trash')</a> 
		</div>
		<div class="clearfix">		</div> 
	</div>
</div>

{{-- Application Change Assignee Modal is now in detail.blade.php (parent page) to avoid Bootstrap issues with AJAX-loaded content --}}

@if(isset($assignees))
{{-- Activity filter + assignee handlers are in application-handlers.js (delegated; works with AJAX-loaded content) --}}
@endif

<script>
(function($) {
	function getCsrfToken() {
		if (typeof App !== 'undefined' && App.getCsrf) {
			return App.getCsrf();
		}
		var meta = document.querySelector('meta[name="csrf-token"]');
		return meta ? meta.getAttribute('content') : '';
	}

	function getUpdateEnrolmentTypeUrl() {
		if (typeof App !== 'undefined' && App.getUrl && App.getUrl('updateApplicationEnrolmentType')) {
			return App.getUrl('updateApplicationEnrolmentType');
		}
		return '/application/update-enrolment-type';
	}

	function getUpdateCompanyNameUrl() {
		if (typeof App !== 'undefined' && App.getUrl && App.getUrl('updateApplicationCompanyName')) {
			return App.getUrl('updateApplicationCompanyName');
		}
		return '/application/update-company-name';
	}

	function syncApplicationEnrolmentTypeSelects() {
		$('.application-enrolment-type-field').each(function() {
			$(this).val($(this).attr('data-enrolment-type') || '');
		});
	}

	function syncApplicationCompanyNameSelects() {
		$('.application-company-name-field').each(function() {
			$(this).val($(this).attr('data-company-name') || '');
		});
	}

	function shouldLockFieldAfterSave(savedValue) {
		var isAdminEditor = (typeof PageConfig !== 'undefined' && PageConfig.canEditApplicationEnrolmentCompanyFields === true);
		return !isAdminEditor && !!savedValue;
	}

	if (!window.__applicationEnrolmentTypeHandlerBound) {
		window.__applicationEnrolmentTypeHandlerBound = true;

		$(document).on('change', '.application-enrolment-type-field', function() {
			var $select = $(this);
			if ($select.prop('disabled')) {
				return;
			}
			var applicationId = $select.data('application-id');
			var previousValue = $select.attr('data-enrolment-type') || '';

			if ($('.popuploader').length) {
				$('.popuploader').show();
			}

			$.ajax({
				url: getUpdateEnrolmentTypeUrl(),
				method: 'POST',
				data: {
					appid: applicationId,
					enrolment_type: $select.val(),
					_token: getCsrfToken()
				},
				success: function(response) {
					if (response && response.status) {
						$select.attr('data-enrolment-type', response.enrolmentType || '');
						$select.val(response.enrolmentType || '');
						if (shouldLockFieldAfterSave(response.enrolmentType || '')) {
							$select.prop('disabled', true);
						}
						if ($('.custom-error-msg').length) {
							$('.custom-error-msg').html('<span class="alert alert-success">' + response.message + '</span>');
						}
						var clientId = (typeof App !== 'undefined' && App.getPageConfig) ? App.getPageConfig('clientId') : null;
						if (clientId && $('.applicationtdata').length) {
							var listUrl = (typeof App !== 'undefined' && App.getUrl && App.getUrl('getApplicationLists'))
								? App.getUrl('getApplicationLists')
								: '/get-application-lists';
							$.ajax({
								url: listUrl,
								type: 'GET',
								data: { id: clientId },
								success: function(html) {
									$('.applicationtdata').html(html);
								}
							});
						}
					} else {
						$select.val(previousValue);
						if ($('.custom-error-msg').length) {
							$('.custom-error-msg').html('<span class="alert alert-danger">' + (response ? response.message : 'Failed to update enrolment type') + '</span>');
						}
					}
				},
				error: function() {
					$select.val(previousValue);
					if ($('.custom-error-msg').length) {
						$('.custom-error-msg').html('<span class="alert alert-danger">Failed to update enrolment type. Please try again.</span>');
					}
				},
				complete: function() {
					if ($('.popuploader').length) {
						$('.popuploader').hide();
					}
				}
			});
		});
	}

	if (!window.__applicationCompanyNameHandlerBound) {
		window.__applicationCompanyNameHandlerBound = true;

		$(document).on('change', '.application-company-name-field', function() {
			var $select = $(this);
			if ($select.prop('disabled')) {
				return;
			}
			var applicationId = $select.data('application-id');
			var previousValue = $select.attr('data-company-name') || '';

			if ($('.popuploader').length) {
				$('.popuploader').show();
			}

			$.ajax({
				url: getUpdateCompanyNameUrl(),
				method: 'POST',
				data: {
					appid: applicationId,
					company_name: $select.val(),
					_token: getCsrfToken()
				},
				success: function(response) {
					if (response && response.status) {
						$select.attr('data-company-name', response.companyName || '');
						$select.val(response.companyName || '');
						if (shouldLockFieldAfterSave(response.companyName || '')) {
							$select.prop('disabled', true);
						}
						if ($('.custom-error-msg').length) {
							$('.custom-error-msg').html('<span class="alert alert-success">' + response.message + '</span>');
						}
						var clientId = (typeof App !== 'undefined' && App.getPageConfig) ? App.getPageConfig('clientId') : null;
						if (clientId && $('.applicationtdata').length) {
							var listUrl = (typeof App !== 'undefined' && App.getUrl && App.getUrl('getApplicationLists'))
								? App.getUrl('getApplicationLists')
								: '/get-application-lists';
							$.ajax({
								url: listUrl,
								type: 'GET',
								data: { id: clientId },
								success: function(html) {
									$('.applicationtdata').html(html);
								}
							});
						}
					} else {
						$select.val(previousValue);
						if ($('.custom-error-msg').length) {
							$('.custom-error-msg').html('<span class="alert alert-danger">' + (response ? response.message : 'Failed to update company name') + '</span>');
						}
					}
				},
				error: function() {
					$select.val(previousValue);
					if ($('.custom-error-msg').length) {
						$('.custom-error-msg').html('<span class="alert alert-danger">Failed to update company name. Please try again.</span>');
					}
				},
				complete: function() {
					if ($('.popuploader').length) {
						$('.popuploader').hide();
					}
				}
			});
		});
	}

	syncApplicationEnrolmentTypeSelects();
	syncApplicationCompanyNameSelects();
})(jQuery);
</script>

