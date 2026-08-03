@extends('layouts.admin')
@section('title', 'Import Individual Agents')

@section('content')

<!-- Main Content -->
<div class="main-content">
	<section class="section">
		<div class="section-body">
			<div class="server-error">
				@include('../Elements/flash-message')
			</div>
			<div class="custom-error-msg">
			</div>
			<div class="row">
				<div class="col-12 col-md-12 col-lg-12">
					<div class="card">
						<div class="card-header">
							<h4>Import Individual Agents</h4>
							
							<div class="card-header-action">
									<a href="{{asset('img/IndividualTemplate.xlsx')}}" class="btn btn-primary">@icon('download') Download Template</a>
								</div>
						</div>
						<div class="card-body">
							<p>This approach allows you to import your huge amount of existing individual agents at once</p>
							<div class="tab-content" id="clientContent">	
								<div class="tab-pane fade show active" id="active" role="tabpanel" aria-labelledby="active-tab">
								{!! Form::open(array('url' => 'agents/import/individual', 'name'=>"add-agents", 'autocomplete'=>'off', "enctype"=>"multipart/form-data"))  !!}
								<input class="form-check-input" type="hidden" id="struture" value="Individual" name="struture">
										<div class="row"> 
											<div class="col-6 col-md-6 col-lg-6">
												<label>Agent Type</label>
												<div class="form-group">
													<div class="form-check form-check-inline">
														<input class="form-check-input" type="checkbox" id="super_agent" value="Super Agent" name="agent_type[]">
														<label class="form-check-label" for="super_agent">Super Agent</label>
													</div>
													<div class="form-check form-check-inline">
														<input class="form-check-input" type="checkbox" id="sub_agent" value="Sub Agent" name="agent_type[]">
														<label class="form-check-label" for="sub_agent">Sub Agent</label>
													</div>
													@if ($errors->has('agent_type'))
														<span class="custom-error" role="alert">
															<strong>{{ @$errors->first('agent_type') }}</strong>
														</span> 
													@endif
												</div>
											
												<div class="form-group"> 
													<label for="related_office">Related Office <span class="span_req">*</span></label>
													<select data-valid="required" class="form-control tomselect" name="related_office">
														<?php
												$branches = \App\Models\Branch::all();
												foreach($branches as $branch){
												?>
													<option  value="{{$branch->id}}">{{$branch->office_name}}</option>
												<?php } ?>
													</select>
													@if ($errors->has('related_office'))
														<span class="custom-error" role="alert">
															<strong>{{ @$errors->first('related_office') }}</strong>
														</span> 
													@endif
												</div>
												<div class="form-group"> 
													<input type="file" class="form-control" name="uploadfile">
												</div>
												
												<div class="form-group">
												{!! Form::submit('Save', ['class'=>'btn btn-primary' ])  !!}
											</div>
											</div>
											<div class="col-6 col-md-6 col-lg-6">
												<p>Add a row for each agents in your company with a column for each file.</p>
												<p>* Fields are mandatory</p>
												<ul>
													<li>Full Name *</li>
													<li>Email * (sample@example.com)</li>
													<li>Phone No. (+xxx - xxxxxxxxxx)</li>
													<li>Street</li>
													<li>City</li>
													<li>State</li>
													<li>Zip Code</li>
													<li>Country</li>
													<li>Claim Revenue Percentage</li>
													<li>Income Sharing Percentage</li>
												</ul>
											</div>
										</div>
									{!! Form::close()  !!}	
								</div>
							</div> 
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
</div>
@endsection
@section('scripts')
<script>
whenTomSelectReady(function () {
    initTomSelectPreserveValue('select[name="related_office"]', { width: '100%', allowClear: true });
});
</script>
@endsection