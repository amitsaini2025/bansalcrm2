@extends('layouts.adminconsole')
@section('title', 'Edit Upload Checklist')

@section('content')
@php
	$isAdminConsole = request()->is('adminconsole/*') || request()->routeIs('adminconsole.*');
	$updateRoute = $isAdminConsole ? 'adminconsole.upload_checklists.update' : 'upload_checklists.update';
	$indexRoute = $isAdminConsole ? 'adminconsole.upload_checklists.index' : 'upload_checklists.index';
	$fileExists = !empty($fetchedData->file) && is_file(public_path('checklists/' . $fetchedData->file));
@endphp

<!-- Main Content -->
<div class="main-content">
	<section class="section">
		<div class="section-body">
			{!! Form::open(['route' => [$updateRoute, $fetchedData->id], 'name' => 'edit-uploadchecklist', 'autocomplete' => 'off', 'enctype' => 'multipart/form-data']) !!}
				<div class="row">
					<div class="col-12">
						<div class="card">
							<div class="card-header">
								<h4>Edit Checklist</h4>
								<div class="card-header-action">
									<a href="{{ route($indexRoute) }}" class="btn btn-primary">@icon('arrow-left') Back</a>
								</div>
							</div>
						</div>
					</div>
					<div class="col-12">
						<div class="card">
							<div class="card-body">
								<div class="row">
									<div class="col-12 col-md-4 col-lg-4">
										<div class="form-group">
											<label for="name">Name <span class="span_req">*</span></label>
											{!! Form::text('name', old('name', $fetchedData->name), ['class' => 'form-control', 'data-valid' => 'required', 'autocomplete' => 'off', 'placeholder' => 'Enter Name']) !!}
											@if ($errors->has('name'))
												<span class="custom-error" role="alert">
													<strong>{{ $errors->first('name') }}</strong>
												</span>
											@endif
										</div>
									</div>
									<div class="col-12 col-md-4 col-lg-4">
										<div class="form-group">
											<label for="checklists">Replace File</label>
											@if(!empty($fetchedData->file))
												<p class="mb-2">
													@if($fileExists)
														<a href="{{ asset('checklists/' . $fetchedData->file) }}" target="_blank" rel="noopener">Current file</a>
													@else
														<span class="text-danger">Current file missing on disk — upload a replacement</span>
													@endif
												</p>
											@endif
											<input type="file" name="checklists" class="form-control" {{ $fileExists ? '' : 'required' }}>
											<small class="text-muted">Leave empty to keep the current file.</small>
											@if ($errors->has('checklists'))
												<span class="custom-error" role="alert">
													<strong>{{ $errors->first('checklists') }}</strong>
												</span>
											@endif
										</div>
									</div>
								</div>
								<div class="form-group float-end">
									{!! Form::submit('Update', ['class' => 'btn btn-primary']) !!}
								</div>
							</div>
						</div>
					</div>
				</div>
			{!! Form::close() !!}
		</div>
	</section>
</div>

@endsection
