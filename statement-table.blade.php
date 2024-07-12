<div id="print_div">
    <div class="pathology-bill">
        <div class="row">
            <div class="col-2" style="width: 20%">
                <p>Date: {!! formatedDate(date('Y-m-d')) !!}</p>
            </div>
            <div class="col-7" style="text-align: center;">
                <p style="color:#0b5ed7;font-size:18px;margin-bottom: -4px !important"><b>{!! $company->name ?? '' !!}</b></p>
                <p style="font-size: 14px;"><b>IPD Billing STATEMENT</b></p>
                <p style="padding-bottom: 5px;font-size: 11px;"><b><span style="color: #698aff;">Billing</span> List</b>
                    <b>From <span style="color:#698aff;" id="">{{ $fromDate ? formatedDate($fromDate) : formatedDate(date('Y-m-d H:i:s')) }}</span> TO <span style="color:#698aff;" id="">{{ $toDate ? formatedDate($toDate) : formatedDate(date('Y-m-d H:i:s')) }}</span>
                    </b></p>
            </div>
            <div class="col-2" style="text-align: right; width:20%">
                <p>Time: {!! formatedTime(date('H:i:s')) !!}</p>
            </div>
        </div>

        <div>
            <table class="table table-borderless report-container">
                <tbody class="report-content">
                    <tr>
                        <td class="report-content-cell">
                            <div class="row">
                                <div class="col-12">
                                    <table class="table table-bordered-print w-100 table-only-header-full-width mb-1" id="quotation_products">
                                        <thead>
                                            <tr class="text-uppercase">
                                                <th class="thbutton">Sl No</th>
                                                <th style="text-align: left;">Admmission No</th>
                                                <th style="text-align: left;">File No</th>
                                                <th style="text-align: left;">Claim No</th>
                                                <th style="text-align: left;">Admission Date Time</th>
                                                <th style="text-align: left;">Patient</th>
                                                <th style="text-align: left;">Source</th>
                                                <th style="text-align: left;">Tpa </th>
                                                <th style="text-align: left;">Casualty</th>
                                                <th style="text-align: left;">Referal</th>
                                                <th style="text-align: left;">Note</th>
                                                <th style="text-align: left;">Referal Partner</th>
                                                <th style="text-align: left;">History</th>
                                                <th style="text-align: left;">Invoice Number</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $i = 1; @endphp
                                            @foreach($ipdAdmission as $list)

                                             <tr>
                                                <td class="text-center">{{ $i }}</td>
                                                <td style="text-align: left;">{{ $list->ipd_admission_no  != 0 ?  $list->ipd_admission_no : ''}}</td>
                                                <td style="text-align: left;">{{ $list->ipd_file_no != 0 ?   $list->ipd_file_no : ''}}</td>
                                                <td style="text-align: left;">{{ $list->claim_number  != 0 ?   $list->claim_number : ''}}</td>
                                                <td style="text-align: left;">{{ $list->admission_date_time  != 0 ?   $list->admission_date_time : ''}}</td>
                                                <td style="text-align: left;">{{ $list->account_name != 0 ?   $list->account_name : ''}}</td>
                                                <td style="text-align: left;">{{ $list->patient_source_id != 0 ?   $list->patient_source_id : '' }}</td>
                                                <td style="text-align: left;">{{ $list->tpa_account_id  != 0 ?   $list->tpa_account_id : ''}}</td>
                                                <td style="text-align: left;">{{ $list->casualty != 0 ?   $list->casualty : ''}}</td>
                                                <td style="text-align: left;">{{ $list->referal_from  != 0 ?   $list->referal_from : ''}}</td>
                                                <td style="text-align: left;">{{ $list->note  != 0 ?   $list->note : ''}}</td>
                                                <td style="text-align: left;">{{ $list->referral_partner_id   != 0 ?   $list->referral_partner_id : ''}}</td>
                                                <td style="text-align: left;">{{ $list->previous_medical_issue   != 0 ?   $list->previous_medical_issue : ''}}</td>
                                                <td style="text-align: left;">{{ $list->sale_invoice_id   != 0 ?   $list->sale_invoice_id : ''}}</td>
                                            </tr>
                                            @php $i++; @endphp
                                            @endforeach
                                            @if(count($ipdAdmission) === 0)
                                            <tr>
                                                <td colspan="14" class="text-center">No Data Found</td>
                                            </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@if(count($ipdAdmission) > 0)
<div class="row">
    <div class="col-md-12" style="text-align: center;">
        <button id="btn_print" type="button" class="btn btn-primary btn-lg"><i class="bi bi-printer text-white"></i> Print Statement </button>
        <button class="btn btn-outline-primary export-statement" id="export-statement"><i class="bi bi-download"></i> Export</button>
        
    </div>

</div>
@endif
