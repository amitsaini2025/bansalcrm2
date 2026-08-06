                                    <div class="student_tabs">
                                        <ul class="nav nav-pills round_tabs" id="student_tabs" role="tablist">
                                            <li class="nav-item">
                                                <a class="nav-link active" data-bs-toggle="tab" id="stdactive-tab" href="#stdactive" role="tab" aria-controls="stdactive" aria-selected="true">Active</a>
                                            </li>

                                            <li class="nav-item">
                                                <a class="nav-link" data-bs-toggle="tab" id="stdinactive-tab" href="#stdinactive" role="tab" aria-controls="stdinactive" aria-selected="false">Inactive</a>
                                            </li>
                                        </ul>
                                        <div class="tab-content" id="studentContent">
                                            <div class="tab-pane fade show active" id="stdactive" role="tabpanel" aria-labelledby="stdactive-tab">
                                                <div class="tab-content" id="studentContent">
                                                    <div class="student_table_panel">
                                                    <div class="student_drop_table_data" style="display: inline-block;margin-right: 10px;">
                                                        <button type="button" class="btn btn-primary dropdown-toggle">@icon('columns')</button>
                                                        <div class="dropdown_list student_dropdown_list">
                                                            <label class="dropdown-option all"><input type="checkbox" value="all" checked /> Display All</label>
                                                            <label class="dropdown-option"><input type="checkbox" value="3" checked /> Student Name</label>
                                                            <label class="dropdown-option"><input type="checkbox" value="4" checked /> Date of Birth</label>
                                                            <label class="dropdown-option"><input type="checkbox" value="5" checked /> Student Id</label>
                                                            <label class="dropdown-option"><input type="checkbox" value="6" checked /> College Name</label>
                                                            <label class="dropdown-option"><input type="checkbox" value="7" checked /> Course Name</label>
                                                            <label class="dropdown-option"><input type="checkbox" value="8" checked /> Start Date</label>
                                                            <label class="dropdown-option"><input type="checkbox" value="9" checked /> End Date</label>
                                                            <label class="dropdown-option"><input type="checkbox" value="10" checked /> Total Course Fee</label>
                                                            <label class="dropdown-option"><input type="checkbox" value="11" checked /> Enrolment Fee</label>
                                                            <label class="dropdown-option"><input type="checkbox" value="12" checked /> Material Fee</label>
                                                            <label class="dropdown-option"><input type="checkbox" value="13" checked /> Tution Fee</label>
                                                            <label class="dropdown-option"><input type="checkbox" value="14" checked /> Fee Reported by College</label>
                                                            <label class="dropdown-option"><input type="checkbox" value="15" checked /> Total Bonus</label>
                                                            <label class="dropdown-option"><input type="checkbox" value="16" checked /> Bonus Pending</label>
                                                            <label class="dropdown-option"><input type="checkbox" value="17" checked /> Scholarship Fee</label>

                                                            <label class="dropdown-option"><input type="checkbox" value="18" checked /> Commission as per Fee reported</label>
                                                            <label class="dropdown-option"><input type="checkbox" value="19" checked /> Commission payable as per anticipated fee</label>
                                                            <label class="dropdown-option"><input type="checkbox" value="20" checked /> Commission paid as per fee Reported</label>
                                                            <label class="dropdown-option"><input type="checkbox" value="21" checked /> Commission Pending</label>

                                                            <label class="dropdown-option"><input type="checkbox" value="22" checked /> Student Status</label>
                                                            <label class="dropdown-option"><input type="checkbox" value="23" checked /> Enrolment Type</label>
                                                            <label class="dropdown-option"><input type="checkbox" value="24" checked /> Company Name</label>
                                                        </div>
                                                    </div>
                                                        <div class="totals-container mb-3 row g-1">
                                                            <div class="col-md-6">Total Commission Claimed: <strong>$<span id="total_commission_claimed">0.00</span></strong></div>
                                                            <div class="col-md-6">Total Commission Paid: <strong>$<span id="total_commission_paid">0.00</span></strong></div>
                                                            <div class="col-md-6">Total Commission Pending: <strong>$<span id="total_commission_pending">0.00</span></strong></div>
                                                            <div class="col-md-6">Total Commission Anticipated: <strong>$<span id="total_commission_anticipated">0.00</span></strong></div>
                                                        </div>
                                                        <div class="student-dt-toolbar-host"></div>
                                                        <div class="table-responsive student_table_data student-dt-table-scroll">
                                                        <table class="table text_wrap table-3">
                                                            <thead>
                                                                <tr>
                                                                    <th>SNo.</th>
                                                                    <th>CRM Ref</th>
                                                                    <th>Student Name</th>
                                                                    <th>Date of Birth</th>
                                                                    <th>Student Id</th>
                                                                    <th>College Name</th>
                                                                    <th>Course Name</th>
                                                                    <th>Start Date</th>
                                                                    <th>End Date</th>
                                                                    <th>Total Course Fee</th>
                                                                    <th>Enrolment Fee</th>
                                                                    <th>Material Fee</th>
                                                                    <th>Tution Fee</th>

                                                                    <!--<th>Total Anticipated Fee</th>-->
                                                                    <th>Fee Reported by College</th>
                                                                    <th>Total Bonus</th>
                                                                    <th>Bonus Pending</th>
                                                                    <th>Scholarship Fee</th>
                                                                    <!--<th>Bonus Paid</th>-->
                                                                    <!--<th>Commission as per anticipated fee</th>-->
                                                                    <th>Commission as per Fee reported</th>
                                                                    <th>Commission payable as per anticipated fee</th>
                                                                    <th>Commission paid as per fee Reported</th>
                                                                    <th>Commission Pending</th>


                                                                    <th>Student Status</th>
                                                                    <th>Enrolment Type</th>
                                                                    <th>Company Name</th>
                                                                    <th style="display: none;">Student ID</th> <!-- Hidden column -->
                                                                    <th>Add Note</th>
                                                                    <th>Action</th>

                                                                </tr>
                                                            </thead>
                                                            <tbody class="invoicedatalist">
                                                                {{-- Rows injected via AJAX by DataTables (datatable-handlers.js) --}}
                                                                @if(false) {{-- dead code preserved for reference only --}}
                                                                <?php
                                                                //dd($fetchedData->id);
                                                                $studentdatas = \App\Models\Application::join('admins', 'applications.client_id', '=', 'admins.id')
                                                                ->leftJoin('partners', 'applications.partner_id', '=', 'partners.id')
                                                                ->leftJoin('products', 'applications.product_id', '=', 'products.id')
                                                                ->leftJoin('application_fee_options', 'applications.id', '=', 'application_fee_options.app_id')
                                                                ->select(
                                                                    'applications.*',
                                                                    'admins.client_id as client_reference',
                                                                    'admins.first_name',
                                                                    'admins.last_name',
                                                                    'admins.dob',
                                                                    'admins.type as person_type',
                                                                    'partners.partner_name',
                                                                    'products.name as coursename',
                                                                    'application_fee_options.total_course_fee_amount',
                                                                    'application_fee_options.enrolment_fee_amount',
                                                                    'application_fee_options.material_fees',
                                                                    'application_fee_options.tution_fees',
                                                                    'application_fee_options.fee_reported_by_college',
                                                                    'application_fee_options.bonus_amount',
                                                                    'application_fee_options.bonus_pending_amount',
                                                                    'application_fee_options.scholarship_fee_amount',
                                                                    'application_fee_options.commission_as_per_fee_reported',
                                                                    'application_fee_options.commission_payable_as_per_anticipated_fee',
                                                                    'application_fee_options.commission_paid_as_per_fee_reported',
                                                                    'application_fee_options.commission_pending'
                                                                )
                                                                ->where('applications.partner_id', $fetchedData->id)
                                                                //->where('applications.overall_status', 0) //overall status = Active
                                                                ->where(function ($query) {
                                                                    $query->where('applications.stage', 'Coe issued')
                                                                          ->orWhere('applications.stage', 'Enrolled')
                                                                          ->orWhere('applications.stage', 'Coe Cancelled');
                                                                })
                                                                ->orderBy('applications.created_at', 'ASC')
                                                                ->distinct()
                                                                ->get(); //dd($studentdatas);

																foreach($studentdatas as $datakey=>$data)
                                                                { 
                                                              	 ?>
                                                                    <tr>
                                                                        <td><?php echo ($datakey+1);?></td>
                                                                        <td>
                                                                            <?php
                                                                            if($data->client_reference){
                                                                                $client_encoded_id = base64_encode(convert_uuencode(@$data->client_id));
                                                                                $isLeadType = strtolower((string) ($data->person_type ?? '')) === 'lead';
                                                                                $detailUrl = $isLeadType
                                                                                    ? route('leads.detail.application', ['id' => $client_encoded_id, 'applicationId' => $data->id])
                                                                                    : route('clients.detail.application', ['id' => $client_encoded_id, 'applicationId' => $data->id]);
                                                                                echo $client_reference = '<a href="'.$detailUrl.'" target="_blank">'.$data->client_reference.'</a>';
                                                                            } else {
                                                                                echo $client_reference = 'N/P';
                                                                            }?>
                                                                        </td>
                                                                        <td>
                                                                            <?php
                                                                            if($data->first_name != ""){
                                                                                echo $full_name = $data->first_name.' '.$data->last_name;
                                                                            } else {
                                                                                echo $full_name = 'N/P';
                                                                            } ?>
                                                                        </td>
                                                                        <td>
                                                                            <?php
                                                                            if($data->dob != ""){ //1992-02-19 Y-m-d
                                                                                $dobArr = explode("-",$data->dob);
                                                                                echo $dob = $dobArr[2]."/".$dobArr[1]."/".$dobArr[0];
                                                                            } else {
                                                                                echo $dob = 'N/P';
                                                                            } ?>
                                                                        </td>

                                                                        <td>
                                                                            <?php
                                                                            if($data->student_id != ""){
                                                                                echo $student_id = $data->student_id;
                                                                            } else {
                                                                                echo $student_id = 'N/P';
                                                                            } ?>
                                                                        </td>
                                                                        <td>
                                                                            <?php
                                                                            if($data->partner_name != ""){
                                                                                echo $partner_name = $data->partner_name;
                                                                            } else {
                                                                                echo $partner_name = 'N/P';
                                                                            } ?>
                                                                        </td>
                                                                        <td>
                                                                            <?php
                                                                            if($data->coursename != ""){
                                                                                $client_encoded_id_course = base64_encode(convert_uuencode(@$data->client_id));
                                                                                $isLeadTypeCourse = strtolower((string) ($data->person_type ?? '')) === 'lead';
                                                                                $courseDetailUrl = $isLeadTypeCourse
                                                                                    ? route('leads.detail.application', ['id' => $client_encoded_id_course, 'applicationId' => $data->id])
                                                                                    : route('clients.detail.application', ['id' => $client_encoded_id_course, 'applicationId' => $data->id]);
                                                                                echo '<a href="'.$courseDetailUrl.'" target="_blank">'.$data->coursename.'</a>';
                                                                            } else {
                                                                                echo $coursename = 'N/P';
                                                                            } ?>
                                                                        </td>
                                                                        <td>
                                                                            <?php
                                                                            if($data->start_date != ""){
                                                                                echo $start_date = date('d/m/Y',strtotime($data->start_date));
                                                                            } else {
                                                                                echo $start_date = 'N/P';
                                                                            } ?>
                                                                        </td>

                                                                        <td>
                                                                            <?php
                                                                            if($data->end_date != ""){
                                                                                echo $end_date = date('d/m/Y',strtotime($data->end_date));
                                                                            } else {
                                                                                echo $end_date = 'N/P';
                                                                            }?>
                                                                        </td>

                                                                        <td>
                                                                            <?php
                                                                            if($data->total_course_fee_amount != ""){
                                                                                echo $total_course_fee_amount = $data->total_course_fee_amount;
                                                                            } else {
                                                                                echo $total_course_fee_amount = '0.00';
                                                                            } ?>
                                                                        </td>

                                                                        <td>
                                                                            <?php
                                                                            if($data->enrolment_fee_amount != ""){
                                                                                echo $enrolment_fee_amount = $data->enrolment_fee_amount;
                                                                            } else {
                                                                                echo $enrolment_fee_amount = '0.00';
                                                                            } ?>
                                                                        </td>

                                                                        <td>
                                                                            <?php
                                                                            if($data->material_fees != ""){
                                                                                echo $material_fees = $data->material_fees;
                                                                            } else {
                                                                                echo $material_fees = '0.00';
                                                                            } ?>
                                                                        </td>

                                                                        <td>
                                                                            <?php
                                                                            if($data->tution_fees != ""){
                                                                                echo $tution_fees = $data->tution_fees;
                                                                            } else {
                                                                                echo $tution_fees = '0.00';
                                                                            } ?>
                                                                        </td>
                                                                        <!--<td>
                                                                            <?php
                                                                            /*if($data->total_anticipated_fee != ""){
                                                                                echo $total_anticipated_fee = $data->total_anticipated_fee;
                                                                            } else {
                                                                                echo $total_anticipated_fee = '0.00';
                                                                            } */?>
                                                                        </td>-->

                                                                        <td>
                                                                            <?php
                                                                            if($data->fee_reported_by_college != ""){
                                                                                echo $fee_reported_by_college = $data->fee_reported_by_college;
                                                                            } else {
                                                                                echo $fee_reported_by_college = '0.00';
                                                                            } ?>
                                                                        </td>

                                                                        <td>
                                                                            <?php
                                                                            if($data->bonus_amount != ""){
                                                                                echo $bonus_amount = $data->bonus_amount;
                                                                            } else {
                                                                                echo $bonus_amount = '0.00';
                                                                            } ?>
                                                                        </td>

                                                                        <td>
                                                                            <?php
                                                                            if($data->bonus_pending_amount != ""){
                                                                                echo $bonus_pending_amount = $data->bonus_pending_amount;
                                                                            } else {
                                                                                echo $bonus_pending_amount = '0.00';
                                                                            } ?>
                                                                        </td>

                                                                        <td>
                                                                            <?php
                                                                            if($data->scholarship_fee_amount != ""){
                                                                                echo $scholarship_fee_amount = $data->scholarship_fee_amount;
                                                                            } else {
                                                                                echo $scholarship_fee_amount = '0.00';
                                                                            } ?>
                                                                        </td>

                                                                        <!--<td>
                                                                            <?php
                                                                            /*if($data->bonus_paid != ""){
                                                                                echo $bonus_paid = $data->bonus_paid;
                                                                            } else {
                                                                                echo $bonus_paid = '0.00';
                                                                            } */?>
                                                                        </td>-->

                                                                        <!--<td>
                                                                            <?php
                                                                            /*if($data->commission_as_per_anticipated_fee != ""){
                                                                                echo $commission_as_per_anticipated_fee = $data->commission_as_per_anticipated_fee;
                                                                            } else {
                                                                                echo $commission_as_per_anticipated_fee = '0.00';
                                                                            }*/ ?>
                                                                        </td>-->

                                                                        <td>
                                                                            <?php
                                                                            if($data->commission_as_per_fee_reported != ""){
                                                                                echo $commission_as_per_fee_reported = $data->commission_as_per_fee_reported;
                                                                            } else {
                                                                                echo $commission_as_per_fee_reported = '0.00';
                                                                            } ?>
                                                                        </td>

                                                                         <td>
                                                                            <?php
                                                                            if($data->commission_payable_as_per_anticipated_fee != ""){
                                                                                echo $commission_payable_as_per_anticipated_fee = $data->commission_payable_as_per_anticipated_fee;
                                                                            } else {
                                                                                echo $commission_payable_as_per_anticipated_fee = '0.00';
                                                                            } ?>
                                                                        </td>

                                                                        <td>
                                                                            <?php
                                                                            if($data->commission_paid_as_per_fee_reported != ""){
                                                                                echo $commission_paid_as_per_fee_reported = $data->commission_paid_as_per_fee_reported;
                                                                            } else {
                                                                                echo $commission_paid_as_per_fee_reported = '0.00';
                                                                            } ?>
                                                                        </td>

                                                                        <td>
                                                                            <?php
                                                                            if($data->commission_pending != ""){
                                                                                echo $commission_pending = $data->commission_pending;
                                                                            } else {
                                                                                echo $commission_pending = '0.00';
                                                                            } ?>
                                                                        </td>

                                                                        <td>
                                                                            <?php
                                                                            if($data->status == 0){
                                                                                echo $student_status = "In Progress";
                                                                            } else if($data->status == 1){
                                                                                echo $student_status = "Completed";
                                                                            } else if($data->status == 2){
                                                                                echo $student_status = "Discontinued";
                                                                            } else if($data->status == 3){
                                                                                echo $student_status = "Cancelled";
                                                                            } else if($data->status == 4){
                                                                                echo $student_status = "Withdrawn";
                                                                            } else if($data->status == 5){
                                                                                echo $student_status = "Deferred";
                                                                            } else if($data->status == 6){
                                                                                echo $student_status = "Future";
                                                                            } else if($data->status == 7){
                                                                                echo $student_status = "VOE";
                                                                            } else if($data->status == 8){
                                                                                echo $student_status = "Refund";
                                                                            }?>
                                                                        </td>
                                                                        <td style="display: none;"><?php echo $data->id;?></td>
                                                                      
                                                                        <td><textarea class="note-field" data-studentid="<?php echo $data->id;?>"><?php echo $data->student_add_notes;?></textarea></td>

                                                                        <td style="white-space: initial;">
                                                                            <div class="dropdown d-inline">
                                                                                <button style="margin-top:3px; margin-bottom:3px;" class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button>
                                                                                <div class="dropdown-menu">
                                                                                    <button class="btn btn-sm btn-primary dropdown-item change-status-btn" data-id="<?php echo $data->id; ?>" data-current-status="<?php echo $data->status; ?>" data-bs-toggle="modal" data-bs-target="#changeStatusModal">Change Status</button>
                                                                                    <!--<a href="javascript:;" datatype="note" class="btn btn-sm btn-primary dropdown-item create_student_note" data-studentid="<?php echo $data->client_id; ?>" data-studentrefno="<?php //echo $data->client_reference; ?>"  data-collegename="<?php //echo $data->partner_name; ?>">Add Student Note</a>-->
                                                                                    
                                                                                    <button class="btn btn-sm btn-primary dropdown-item change-application-overall-status-btn" data-id="<?php echo $data->id; ?>" data-application-overall-status="<?php echo $data->overall_status; ?>" data-bs-toggle="modal" data-bs-target="#changeApplicationOverallStatusModal">Change Application To Inactive</button>
                                                                                </div>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                <?php
                                                                } //end foreach?>
                                                                @endif
                                                            </tbody>
                                                          
                                                            
                                                        </table>
                                                    </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="tab-pane fade" id="stdinactive" role="tabpanel" aria-labelledby="stdinactive-tab">
                                                <div class="student_table_panel1">
                                                <div class="student_drop_table_data1" style="display: inline-block;margin-right: 10px;">
                                                    <button type="button" class="btn btn-primary dropdown-toggle">@icon('columns')</button>
                                                    <div class="dropdown_list student_dropdown_list1">
                                                        <label class="dropdown-option all"><input type="checkbox" value="all" checked /> Display All</label>
                                                        <label class="dropdown-option"><input type="checkbox" value="3" checked /> Student Name</label>
                                                        <label class="dropdown-option"><input type="checkbox" value="4" checked /> Date of Birth</label>
                                                        <label class="dropdown-option"><input type="checkbox" value="5" checked /> Student Id</label>
                                                        <label class="dropdown-option"><input type="checkbox" value="6" checked /> College Name</label>
                                                        <label class="dropdown-option"><input type="checkbox" value="7" checked /> Course Name</label>
                                                        <label class="dropdown-option"><input type="checkbox" value="8" checked /> Start Date</label>
                                                        <label class="dropdown-option"><input type="checkbox" value="9" checked /> End Date</label>
                                                        <label class="dropdown-option"><input type="checkbox" value="10" checked /> Total Course Fee</label>
                                                        <label class="dropdown-option"><input type="checkbox" value="11" checked /> Enrolment Fee</label>
                                                        <label class="dropdown-option"><input type="checkbox" value="12" checked /> Material Fee</label>
                                                        <label class="dropdown-option"><input type="checkbox" value="13" checked /> Tution Fee</label>
                                                        <label class="dropdown-option"><input type="checkbox" value="14" checked /> Fee Reported by College</label>
                                                        <label class="dropdown-option"><input type="checkbox" value="15" checked /> Total Bonus</label>
                                                        <label class="dropdown-option"><input type="checkbox" value="16" checked /> Bonus Pending</label>
                                                        <label class="dropdown-option"><input type="checkbox" value="17" checked /> Scholarship Fee</label>

                                                        <label class="dropdown-option"><input type="checkbox" value="18" checked /> Commission as per Fee reported</label>
                                                        <label class="dropdown-option"><input type="checkbox" value="19" checked /> Commission payable as per anticipated fee</label>
                                                        <label class="dropdown-option"><input type="checkbox" value="20" checked /> Commission paid as per fee Reported</label>
                                                        <label class="dropdown-option"><input type="checkbox" value="21" checked /> Commission Pending</label>
                                                        <label class="dropdown-option"><input type="checkbox" value="22" checked /> Student Status</label>
                                                        <label class="dropdown-option"><input type="checkbox" value="23" checked /> Enrolment Type</label>
                                                        <label class="dropdown-option"><input type="checkbox" value="24" checked /> Company Name</label>
                                                    </div>
                                                </div>
                                                <div class="student-dt-toolbar-host"></div>
                                                <div class="table-responsive student_table_data1 student-dt-table-scroll">
                                                    <table class="table text_wrap table-31">
                                                        <thead>
                                                            <tr>
                                                                <th>SNo.</th>
                                                                <th>CRM Ref</th>
                                                                <th>Student Name</th>
                                                                <th>Date of Birth</th>
                                                                <th>Student Id</th>
                                                                <th>College Name</th>
                                                                <th>Course Name</th>
                                                                <th>Start Date</th>
                                                                <th>End Date</th>
                                                                <th>Total Course Fee</th>
                                                                <th>Enrolment Fee</th>
                                                                <th>Material Fee</th>
                                                                <th>Tution Fee</th>

                                                                <!--<th>Total Anticipated Fee</th>-->
                                                                <th>Fee Reported by College</th>
                                                                <th>Total Bonus</th>
                                                                <th>Bonus Pending</th>
                                                                <th>Scholarship Fee</th>
                                                                <!--<th>Bonus Paid</th>-->
                                                                <!--<th>Commission as per anticipated fee</th>-->
                                                                <th>Commission as per Fee reported</th>
                                                                <th>Commission payable as per anticipated fee</th>
                                                                <th>Commission paid as per fee Reported</th>
                                                                <th>Commission Pending</th>

                                                                <th>Student Status</th>
                                                                <th>Enrolment Type</th>
                                                                <th>Company Name</th>
                                                                <th style="display: none;">Student ID</th> <!-- Hidden column -->
                                                                <th>Add Note</th>
                                                                <th>Action</th>

                                                            </tr>
                                                        </thead>
                                                        <tbody class="invoicedatalist">
                                                            {{-- Rows injected via AJAX by DataTables (datatable-handlers.js) --}}
                                                            @if(false) {{-- dead code preserved for reference only --}}
                                                            <?php
                                                            //dd($fetchedData->id);
                                                            $studentdatas1 = \App\Models\Application::join('admins', 'applications.client_id', '=', 'admins.id')
                                                            ->leftJoin('partners', 'applications.partner_id', '=', 'partners.id')
                                                            ->leftJoin('products', 'applications.product_id', '=', 'products.id')
                                                            ->leftJoin('application_fee_options', 'applications.id', '=', 'application_fee_options.app_id')
                                                            ->select(
                                                                'applications.*',
                                                                'admins.client_id as client_reference',
                                                                'admins.first_name',
                                                                'admins.last_name',
                                                                'admins.dob',
                                                                'admins.type as person_type',
                                                                'partners.partner_name',
                                                                'products.name as coursename',
                                                                'application_fee_options.total_course_fee_amount',
                                                                'application_fee_options.enrolment_fee_amount',
                                                                'application_fee_options.material_fees',
                                                                'application_fee_options.tution_fees',
                                                                'application_fee_options.fee_reported_by_college',
                                                                'application_fee_options.bonus_amount',
                                                                'application_fee_options.bonus_pending_amount',
                                                                'application_fee_options.scholarship_fee_amount',
                                                                'application_fee_options.commission_as_per_fee_reported',
                                                                'application_fee_options.commission_payable_as_per_anticipated_fee',
                                                                'application_fee_options.commission_paid_as_per_fee_reported',
                                                                'application_fee_options.commission_pending'
                                                            )
                                                            ->where('applications.partner_id', $fetchedData->id)
                                                            ->where('applications.overall_status', 1) //overall status = Inactive
                                                            ->where(function ($query) {
                                                                $query->where('applications.stage', 'Coe issued')
                                                                        ->orWhere('applications.stage', 'Enrolled')
                                                                        ->orWhere('applications.stage', 'Coe Cancelled');
                                                            })
                                                            ->orderBy('applications.created_at', 'ASC')
                                                            ->get(); //dd($studentdatas1);

															foreach($studentdatas1 as $datakey1=>$data1)
                                                            { 
                                                              ?>
                                                                <tr>
                                                                    <td><?php echo ($datakey1+1);?></td>
                                                                    <td>
                                                                        <?php
                                                                        if($data1->client_reference){
                                                                            $client_encoded_id1 = base64_encode(convert_uuencode(@$data1->client_id));
                                                                            $isLeadType1 = strtolower((string) ($data1->person_type ?? '')) === 'lead';
                                                                            $detailUrl1 = $isLeadType1
                                                                                ? route('leads.detail.application', ['id' => $client_encoded_id1, 'applicationId' => $data1->id])
                                                                                : route('clients.detail.application', ['id' => $client_encoded_id1, 'applicationId' => $data1->id]);
                                                                           echo $client_reference1 = '<a href="'.$detailUrl1.'" target="_blank">'.$data1->client_reference.'</a>';
                                                                        } else {
                                                                            echo $client_reference1 = 'N/P';
                                                                        }?>
                                                                    </td>
                                                                    <td>
                                                                        <?php
                                                                        if($data1->first_name != ""){
                                                                            echo $full_name1 = $data1->first_name.' '.$data1->last_name;
                                                                        } else {
                                                                            echo $full_name1 = 'N/P';
                                                                        } ?>
                                                                    </td>
                                                                    <td>
                                                                        <?php
                                                                        if($data1->dob != ""){ //1992-02-19 Y-m-d
                                                                            $dobArr1 = explode("-",$data1->dob);
                                                                            echo $dob1 = $dobArr1[2]."/".$dobArr1[1]."/".$dobArr1[0];
                                                                        } else {
                                                                            echo $dob1 = 'N/P';
                                                                        } ?>
                                                                    </td>

                                                                    <td>
                                                                        <?php
                                                                        if($data1->student_id != ""){
                                                                            echo $student_id1 = $data1->student_id;
                                                                        } else {
                                                                            echo $student_id1 = 'N/P';
                                                                        } ?>
                                                                    </td>
                                                                    <td>
                                                                        <?php
                                                                        if($data1->partner_name != ""){
                                                                            echo $partner_name1 = $data1->partner_name;
                                                                        } else {
                                                                            echo $partner_name1 = 'N/P';
                                                                        } ?>
                                                                    </td>
                                                                    <td>
                                                                        <?php
                                                                        if($data1->coursename != ""){
                                                                            $client_encoded_id_course1 = base64_encode(convert_uuencode(@$data1->client_id));
                                                                            $isLeadTypeCourse1 = strtolower((string) ($data1->person_type ?? '')) === 'lead';
                                                                            $courseDetailUrl1 = $isLeadTypeCourse1
                                                                                ? route('leads.detail.application', ['id' => $client_encoded_id_course1, 'applicationId' => $data1->id])
                                                                                : route('clients.detail.application', ['id' => $client_encoded_id_course1, 'applicationId' => $data1->id]);
                                                                            echo '<a href="'.$courseDetailUrl1.'" target="_blank">'.$data1->coursename.'</a>';
                                                                        } else {
                                                                            echo $coursename1 = 'N/P';
                                                                        } ?>
                                                                    </td>
                                                                    <td>
                                                                        <?php
                                                                        if($data1->start_date != ""){
                                                                            echo $start_date1 = date('d/m/Y',strtotime($data1->start_date));
                                                                        } else {
                                                                            echo $start_date1 = 'N/P';
                                                                        } ?>
                                                                    </td>

                                                                    <td>
                                                                        <?php
                                                                        if($data1->end_date != ""){
                                                                            echo $end_date1 = date('d/m/Y',strtotime($data1->end_date));
                                                                        } else {
                                                                            echo $end_date1 = 'N/P';
                                                                        }?>
                                                                    </td>

                                                                    <td>
                                                                        <?php
                                                                        if($data1->total_course_fee_amount != ""){
                                                                            echo $total_course_fee_amount1 = $data1->total_course_fee_amount;
                                                                        } else {
                                                                            echo $total_course_fee_amount1 = '0.00';
                                                                        } ?>
                                                                    </td>

                                                                    <td>
                                                                        <?php
                                                                        if($data1->enrolment_fee_amount != ""){
                                                                            echo $enrolment_fee_amount1 = $data1->enrolment_fee_amount;
                                                                        } else {
                                                                            echo $enrolment_fee_amount1 = '0.00';
                                                                        } ?>
                                                                    </td>

                                                                    <td>
                                                                        <?php
                                                                        if($data1->material_fees != ""){
                                                                            echo $material_fees1 = $data1->material_fees;
                                                                        } else {
                                                                            echo $material_fees1 = '0.00';
                                                                        } ?>
                                                                    </td>

                                                                    <td>
                                                                        <?php
                                                                        if($data1->tution_fees != ""){
                                                                            echo $tution_fees1 = $data1->tution_fees;
                                                                        } else {
                                                                            echo $tution_fees1 = '0.00';
                                                                        } ?>
                                                                    </td>
                                                                    <!--<td>
                                                                        <?php
                                                                        /*if($data1->total_anticipated_fee != ""){
                                                                            echo $total_anticipated_fee1 = $data1->total_anticipated_fee;
                                                                        } else {
                                                                            echo $total_anticipated_fee1 = '0.00';
                                                                        } */?>
                                                                    </td>-->

                                                                    <td>
                                                                        <?php
                                                                        if($data1->fee_reported_by_college != ""){
                                                                            echo $fee_reported_by_college1 = $data1->fee_reported_by_college;
                                                                        } else {
                                                                            echo $fee_reported_by_college1 = '0.00';
                                                                        } ?>
                                                                    </td>

                                                                    <td>
                                                                        <?php
                                                                        if($data1->bonus_amount != ""){
                                                                            echo $bonus_amount1 = $data1->bonus_amount;
                                                                        } else {
                                                                            echo $bonus_amount1 = '0.00';
                                                                        } ?>
                                                                    </td>

                                                                    <td>
                                                                        <?php
                                                                        if($data1->bonus_pending_amount != ""){
                                                                            echo $bonus_pending_amount1 = $data1->bonus_pending_amount;
                                                                        } else {
                                                                            echo $bonus_pending_amount1 = '0.00';
                                                                        } ?>
                                                                    </td>

                                                                    <td>
                                                                        <?php
                                                                        if($data1->scholarship_fee_amount != ""){
                                                                            echo $scholarship_fee_amount1 = $data1->scholarship_fee_amount;
                                                                        } else {
                                                                            echo $scholarship_fee_amount1 = '0.00';
                                                                        } ?>
                                                                    </td>

                                                                    <!--<td>
                                                                        <?php
                                                                        /*if($data1->bonus_paid != ""){
                                                                            echo $bonus_paid1 = $data1->bonus_paid;
                                                                        } else {
                                                                            echo $bonus_paid1 = '0.00';
                                                                        } */?>
                                                                    </td>-->

                                                                    <!--<td>
                                                                        <?php
                                                                        /*if($data1->commission_as_per_anticipated_fee != ""){
                                                                            echo $commission_as_per_anticipated_fee1 = $data1->commission_as_per_anticipated_fee;
                                                                        } else {
                                                                            echo $commission_as_per_anticipated_fee1 = '0.00';
                                                                        }*/ ?>
                                                                    </td>-->

                                                                    <td>
                                                                        <?php
                                                                        if($data1->commission_as_per_fee_reported != ""){
                                                                            echo $commission_as_per_fee_reported1 = $data1->commission_as_per_fee_reported;
                                                                        } else {
                                                                            echo $commission_as_per_fee_reported1 = '0.00';
                                                                        } ?>
                                                                    </td>

                                                                    <td>
                                                                        <?php
                                                                        if($data1->commission_payable_as_per_anticipated_fee != ""){
                                                                            echo $commission_payable_as_per_anticipated_fee1 = $data1->commission_payable_as_per_anticipated_fee;
                                                                        } else {
                                                                            echo $commission_payable_as_per_anticipated_fee1 = '0.00';
                                                                        } ?>
                                                                    </td>

                                                                    <td>
                                                                        <?php
                                                                        if($data1->commission_paid_as_per_fee_reported != ""){
                                                                            echo $commission_paid_as_per_fee_reported1 = $data1->commission_paid_as_per_fee_reported;
                                                                        } else {
                                                                            echo $commission_paid_as_per_fee_reported1 = '0.00';
                                                                        } ?>
                                                                    </td>

                                                                    <td>
                                                                        <?php
                                                                        if($data1->commission_pending != ""){
                                                                            echo $commission_pending1 = $data1->commission_pending;
                                                                        } else {
                                                                            echo $commission_pending1 = '0.00';
                                                                        } ?>
                                                                    </td>

                                                                    <td>
                                                                        <?php
                                                                        if($data1->status == 0){
                                                                            echo $student_status1 = "In Progress";
                                                                        } else if($data1->status == 1){
                                                                            echo $student_status1 = "Completed";
                                                                        } else if($data1->status == 2){
                                                                            echo $student_status1 = "Discontinued";
                                                                        } else if($data1->status == 3){
                                                                            echo $student_status1 = "Cancelled";
                                                                        } else if($data1->status == 4){
                                                                            echo $student_status1 = "Withdrawn";
                                                                        } else if($data1->status == 5){
                                                                            echo $student_status1 = "Deferred";
                                                                        } else if($data1->status == 6){
                                                                            echo $student_status1 = "Future";
                                                                        } else if($data1->status == 7){
                                                                            echo $student_status1 = "VOE";
                                                                        } else if($data1->status == 8){
                                                                            echo $student_status1 = "Refund";
                                                                        }?>
                                                                    </td>
                                                                    <td style="display: none;"><?php echo $data1->id;?></td>
                                                                    <td><textarea class="note-field1" data-studentid="<?php echo $data1->id;?>"><?php echo $data1->student_add_notes;?></textarea></td>
                                                                    
                                                                    <td style="white-space: initial;">
                                                                        <div class="dropdown d-inline">
                                                                            <button style="margin-top:3px; margin-bottom:3px;" class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button>
                                                                            <div class="dropdown-menu">
                                                                                <button class="btn btn-sm btn-primary dropdown-item change-status-btn" data-id="<?php echo $data1->id; ?>" data-current-status="<?php echo $data1->status; ?>" data-bs-toggle="modal" data-bs-target="#changeStatusModal">Change Status</button>
                                                                                
                                                                                <button class="btn btn-sm btn-primary dropdown-item change-application-overall-status-btn" data-id="<?php echo $data1->id; ?>" data-application-overall-status="<?php echo $data1->overall_status; ?>" data-bs-toggle="modal" data-bs-target="#changeApplicationOverallStatusModal">Change Application To Active</button>
                                                                            </div>
                                                                        </div>
                                                                    </td>

                                                                </tr>
                                                            <?php
                                                            } //end foreach?>
                                                            @endif
                                                        </tbody>
                                                      
                                                        <tfoot>
                                                            <tr>
                                                                <th colspan="17" style="text-align: right;">Total</th>
                                                                <th id="total_commission_as_per_fee_reported1">0.00</th>
                                                                <th id="total_commission_anticipated1">0.00</th>
                                                                <th id="total_commission_paid_as_per_fee_reported1">0.00</th>
                                                                <th id="total_commission_pending1">0.00</th>
                                                            </tr>
                                                        </tfoot>
                                                    </table>
                                                </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
