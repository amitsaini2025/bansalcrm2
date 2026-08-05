@extends('layouts.adminconsole')
@section('title', 'Upload Checklists')

@section('content')
@php
	$isAdminConsole = request()->is('adminconsole/*') || request()->routeIs('adminconsole.*');
	$storeRoute = $isAdminConsole ? 'adminconsole.upload_checklists.store' : 'upload_checklistsupload';
	$editRoute = $isAdminConsole ? 'adminconsole.upload_checklists.edit' : 'upload_checklists.edit';
	$backRoute = $isAdminConsole ? 'adminconsole.documentchecklist.index' : 'upload_checklists.index';
@endphp

<!-- Main Content -->
<div class="main-content">
	<section class="section">
		<div class="section-body">
			{!! Form::open(['route' => $storeRoute, 'name' => 'add-uploadchecklist', 'autocomplete' => 'off', 'enctype' => 'multipart/form-data']) !!}
				<div class="row">
					<div class="col-12 col-md-12 col-lg-12">
						<div class="card">
							<div class="card-header">
								<h4>Checklists</h4>
								<div class="card-header-action">
									@if(\Illuminate\Support\Facades\Route::has($backRoute))
										<a href="{{ route($backRoute) }}" class="btn btn-primary">@icon('arrow-left') Back</a>
									@endif
								</div>
							</div>
						</div>
					</div>
				<div class="col-12">
						<div class="card">
							<div class="card-body">
								<div id="accordion">
									<div class="accordion">
										<div class="accordion-header" role="button" data-bs-toggle="collapse" data-bs-target="#primary_info_add" aria-expanded="true">
											<h4>Primary Information</h4>
										</div>
										<div class="accordion-body collapse show" id="primary_info_add" data-parent="#accordion">
											<div class="row">
												<div class="col-12 col-md-4 col-lg-4">
													<div class="form-group">
														<label for="name">Name <span class="span_req">*</span></label>
														{!! Form::text('name', old('name'), ['class' => 'form-control', 'data-valid' => 'required', 'autocomplete' => 'off', 'placeholder' => 'Enter Name']) !!}
														@if ($errors->has('name'))
															<span class="custom-error" role="alert">
																<strong>{{ @$errors->first('name') }}</strong>
															</span>
														@endif
													</div>
												</div>
										<div class="col-12 col-md-4 col-lg-4">
													<div class="form-group">
														<label for="checklists">File <span class="span_req">*</span></label>
													<input data-valid="required" type="file" name="checklists" class="form-control" required>
														@if ($errors->has('checklists'))
															<span class="custom-error" role="alert">
																<strong>{{ @$errors->first('checklists') }}</strong>
															</span>
														@endif
													</div>
												</div>

											</div>
										</div>
									</div>
								</div>
								<div class="form-group float-end">
									{!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
								</div>
							</div>
						</div>


						<div class="card">
							<div class="card-body">
								<div id="accordion">
									<div class="accordion">
										<div class="accordion-header" role="button" data-bs-toggle="collapse" data-bs-target="#primary_info_list" aria-expanded="true">
											<h4>Checklists</h4>
										</div>
										<div class="accordion-body collapse show" id="primary_info_list" data-parent="#accordion">
											<div class="table-responsive common_table">
                                                <table class="table text_wrap">
                                                  <thead>
                                                      <tr>
                                                          <th>Name</th>
                                                          <th>File</th>
                                                          <th>Action</th>
                                                      </tr>
                                                  </thead>
                                                    @if(@$totalData !== 0)
                                                  <tbody class="tdata">
                                                  @foreach (@$lists as $list)
                                                      @php
                                                          $fileExists = !empty($list->file) && is_file(public_path('checklists/' . $list->file));
                                                      @endphp
                                                      <tr id="id_{{@$list->id}}">
                                                          <td>{{ @$list->name == "" ? config('constants.empty') : \Illuminate\Support\Str::limit(@$list->name, 50, '...') }}</td>

                                                          <td>
                                                              @if($fileExists)
                                                                  <a href="{{ asset('checklists/' . $list->file) }}" target="_blank" rel="noopener">File</a>
                                                              @elseif(!empty($list->file))
                                                                  <span class="text-danger" title="File missing on disk — re-upload via Edit">Missing</span>
                                                              @else
                                                                  <span class="text-muted">No file</span>
                                                              @endif
                                                          </td>
                                                          <td>
                                                            <div class="dropdown d-inline">
                                                                <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button>
                                                                <div class="dropdown-menu">
                                                                    <a class="dropdown-item has-icon" href="{{ route($editRoute, $list->id) }}">@icon('edit', 'regular') Edit</a>
                                                                    <a class="dropdown-item has-icon" href="javascript:;" onClick="deleteAction({{ (int) $list->id }}, 'upload_checklists')">@icon('trash') Delete</a>
                                                                </div>
                                                            </div>
                                                          </td>
                                                      </tr>
                                                  @endforeach
                                                  </tbody>
                                                  @else
                                                  <tbody>
                                                      <tr>
                                                          <td style="text-align:center;" colspan="3">
                                                              No Record found
                                                          </td>
                                                      </tr>
                                                  </tbody>
                                                  @endif
                                            </table>
						</div>
										</div>
									</div>
								</div>

							</div>

                            <div class="card-footer">
                                {!! $lists->appends(\Request::except('page'))->render() !!}
                            </div>

						</div>
					</div>
				</div>
			 {!! Form::close() !!}
		</div>
	</section>
</div>

@endsection
