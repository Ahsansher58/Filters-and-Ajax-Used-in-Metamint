<?php

namespace App\Http\Controllers\IpdAdmission;

use App\Http\Controllers\Controller;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use App\Models\IpdAdmission;
use App\Models\SymptomClassification;
use App\Models\Symptom;
use App\Models\EnquirySource;
use App\Models\MasterType;
use App\Models\MenuModelPermission;
use App\Models\Account;
use App\Models\BodyVital;
use App\Models\IpdBodyVitalHistory;
use App\Models\IpdSymptomHistory;
use App\Models\IpdBedHistory;
use App\Models\BedGroup;
use App\Models\Bed;
use App\Models\IpdConsultantHistory;
use App\Models\AccountContact;
use App\Models\Gender;
use App\Models\MaritalStatus;
use App\Models\BloodGroup;
use App\Models\AccountImage;
use App\Models\AccountTitle;
use App\Models\AccountTransaction;
use App\Models\AcReceipt;
use App\Models\AcReceiptDetail;
use App\Models\VoucherMaster;
use App\Models\VoucherType;
use App\Models\DischargeType;
use App\Models\IpdAdmissionDischarged;
use App\Models\IpdAdmissionDeathDischarged;
use App\Models\IpdAdmissionReferralDischarged;
use App\Models\OpdBookings;
use App\Models\SaleInvoice;
use App\Models\SaleInvoiceDetail;
use App\Models\VoucherCollection;
use App\Models\VoucherCollectionDetail;
use App\Models\IpdDischarge;
use App\Models\IpdDischargeTypeSetting;
use App\Models\IpdDischargeDetail;
use App\Models\SaleInvoiceBatch;
use App\Models\Sales\SaleReturns;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Session;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ProductBrand;
use App\Models\Product;
use App\Models\PaymentTerm;
use App\Models\ProductTestPackage;
use App\Models\SaleInvoiceSubDetails;
use App\Models\SpecialCase;
use App\Models\LabBillingReport;
use App\Models\Department;
use App\Models\TestPackage;
use App\Models\Company;
use App\Models\TaskStatus;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\Auth;
use App\Traits\AcReceiptAndTransactionAllTrait;
use App\Traits\BillingConceptTrait;
use App\Traits\TransactionSummeryTrait;;
use Mpdf\Tag\Q;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\IpdBillingStatementExport;



class IpdAdmissionsController extends Controller
{
    function __construct()
    {
        $this->middleware(function ($request, $next){
            $authRolePermissions = \Session::get('rolePermissions');

            if (!in_array('ipd-admission-list', $authRolePermissions)) {
                return redirect('dashboard')->with('info', 'You don\'t have enough permission to access this page.');
            } else {
                return $next($request);
            }
        });
    }

    public function index(Request $request)
    {
        \Session::forget('ipd_billing_products_cart');

        //\DB::enableQueryLog();
        $data = IpdAdmission::select([
                    'ipd_admissions.id',
                    'ipd_admissions.ipd_admission_no',
                    'ipd_admissions.ipd_file_no',
                    'ipd_admissions.tpa_account_id',
                    'ipd_admissions.casualty',
                    'ipd_admissions.admission_date_time',
                    'ipd_admissions.referal_from',
                    'ipd_admissions.note',
                    'enquiry_sources.enquiry_source_name',
                    'ipd_admissions.patient_id as patient_id',
                    'sale_invoices.id as sale_invoices_name',
                    'sale_invoices.operator_id',
                    'ipd_admission_discharged.id as ipd_admission_discharged_id',
                    'users.prepared_by',
                ])
                ->selectRaw('GROUP_CONCAT(beds.name) as bed')
                ->leftjoin('ipd_consultant_histories', 'ipd_consultant_histories.ipd_admission_id', 'ipd_admissions.id')
                ->leftjoin('enquiry_sources', 'enquiry_sources.id', 'ipd_admissions.patient_source_id')
                ->leftjoin('ipd_bed_histories', 'ipd_bed_histories.ipd_admission_id', 'ipd_admissions.id')
                ->leftjoin('ipd_admission_discharged', 'ipd_admission_discharged.ipd_admission_id', 'ipd_admissions.id')
                ->leftjoin('beds', 'beds.id', 'ipd_bed_histories.bed_no_id')
                ->leftjoin('sale_invoices', function ($join) {
                    $join->on('ipd_admissions.patient_id', '=', 'sale_invoices.patient_id')
                        ->where('sale_invoices.invoice_type', '=', 'ipd-billings');
                })
                ->leftjoin('users', 'users.id', 'sale_invoices.operator_id')
                ->with('contacts', 'consultantHistory','getTpa')
                ->where('ipd_consultant_histories.is_main_dr', 1)
                ->whereNull('ipd_admission_discharged.id')
                ->whereNull('ipd_admissions.deleted_at')
                ->groupBy('ipd_admissions.id')
                ->orderBy('ipd_admissions.ipd_admission_no', 'DESC');


        if ($request->ajax()) {
            $sort_by      = $request->get('sortby') ?? 10;
            $sort_type    = $request->get('sorttype');
            $search_query = $request->get('query');
            $search_type = $request->get('search_type');

            $data = $data->when(!empty($search_query) && !empty($search_type),
                function ($query) use ($search_query, $search_type) {
                    if ($search_type == 'patient_title') {
                        $query->whereHas('contacts.account_title', function ($contacts) use ($search_query) {
                            $contacts->where('name', 'like', '%' . $search_query . '%');
                        });
                    } else if ($search_type == 'patient_name') {
                        $query->whereHas('contacts', function ($contacts) use ($search_query) {
                            $contacts->where('name', 'like', '%' . $search_query . '%');
                        });
                    } else if ($search_type == 'patient_code') {
                        $query->whereHas('contacts', function ($contacts) use ($search_query) {
                            $contacts->where('code', 'like', '%' . $search_query . '%');
                        });
                    } else if ($search_type == 'consultant_title') {
                        $query->whereHas('consultantHistory.consultants.account_title', function ($contacts) use ($search_query) {
                            $contacts->where('name', 'like', '%' . $search_query . '%');
                        });
                    } else if ($search_type == 'consultant_name') {
                        $query->whereHas('consultantHistory.consultants', function ($contacts) use ($search_query) {
                            $contacts->where('name', 'like', '%' . $search_query . '%');
                        });
                    } else if ($search_type == 'ipd_admission_no') {
                        $query->where('ipd_admissions.ipd_admission_no', 'like', '%'.$search_query.'%');
                    } else if ($search_type == 'ipd_file_no') {
                        $query->where('ipd_admissions.ipd_file_no', 'like', '%'.$search_query.'%');
                    }
                })
                ->paginate($sort_by);

                return view('ipd-admissions.table', compact('data'));
            }
            else
            {
                $data = $data->paginate(10);
            return view('ipd-admissions.index', compact('data'));
        }
    }



    public function statement(Request $request)
    {
        $companies = Company::select('id', 'name')->get();
        $users     = SaleInvoice::select('users.id', 'users.name', 'users.email')
            ->leftjoin('users', 'users.id','sale_invoices.operator_id')
            ->groupBy('sale_invoices.operator_id')
            ->get();

        if ($request->ajax()) {
            $company = Company::find(Auth::user()->company_id);
            $collection_status  = $request->collection_status;
            $companyDateFormate = phpToJsDateFormat($this->companyDateFormate());
            $listing_type = $request->listing_type;

            $from_date = $request->from_date ?
                Carbon::createFromFormat($this->companyDateFormate(), $request->from_date)->format('Y-m-d')
                    :date('Y-m-d');
            $to_date   = $request->to_date ?
                Carbon::createFromFormat($this->companyDateFormate(), $request->to_date)->format('Y-m-d')
                    :date('Y-m-d');

            $filters                                  = $where        = [];
            $filters['from_date']                     = date('Y-m-d', strtotime($from_date));
            $filters['to_date']                       = date('Y-m-d', strtotime($to_date));
            $filters['company']                       = $request->company != 'null' ? $request->company : '';
            $filters['created_by']                    = $request->created_by != 'null' ? $request->created_by : '';
            $filters['search_account']                = $request->search_account != 'null' ? $request->search_account : '';
            $filters['category']                      = $request->category != 'null' ? $request->category : '';
            $filters['listing_type']                  = $request->listing_type != 'null' ? $request->listing_type : '';
            $filters['collection_status']             = $request->collection_status != 'null' ? $request->collection_status : '';

            $ipdAdmission = IpdAdmission::getStatement($where, $filters);

         
    
            $fromDate = date('Y-m-d H:i:s',strtotime($from_date));
            $toDate   = date('Y-m-d H:i:s',strtotime($to_date));

            return view('ipd-billings.statement-table', compact([
                'fromDate',
                'toDate',
                'companyDateFormate',
                'ipdAdmission' ,
                'collection_status',
            ]));
        }

        $productCategory = ProductCategory::select(['id', 'name', 'is_default'])->where(['status' => 1])->get();

        return view('ipd-billings.statement', compact([
            'companies',
            'productCategory',
            'users' , 
        ]));
    }


    public function exportStatement(Request $request)
    {

        $companyDateFormate = phpToJsDateFormat($this->companyDateFormate());

        $from_date = $request->from_date ?
            Carbon::createFromFormat($this->companyDateFormate(), $request->from_date)->format('Y-m-d')
                :date('Y-m-d');
        $to_date   = $request->to_date ?
            Carbon::createFromFormat($this->companyDateFormate(), $request->to_date)->format('Y-m-d')
                :date('Y-m-d');

            $filters                                  = $where        = [];
            $filters['from_date']                     = date('Y-m-d', strtotime($from_date));
            $filters['to_date']                       = date('Y-m-d', strtotime($to_date));
            $filters['company']                       = $request->company != 'null' ? $request->company : '';
            $filters['created_by']                    = $request->created_by != 'null' ? $request->created_by : '';
            $filters['search_account']                = $request->search_account != 'null' ? $request->search_account : '';
            $filters['category']                      = $request->category != 'null' ? $request->category : '';
            $filters['listing_type']                  = $request->listing_type != 'null' ? $request->listing_type : '';
            $filters['collection_status']             = $request->collection_status != 'null' ? $request->collection_status : '';           
  

        $data = IpdAdmission::getStatement([], $filters)->toArray();
        
        $fileName = 'Ipd-Billing-Statements'.' From '.$from_date.' To '.$to_date.'.xlsx';

        return Excel::download(new IpdBillingStatementExport($data), $fileName);
    }
    

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request,$id='')
    {
        $sources           = EnquirySource::select('id', 'enquiry_source_name')->get();
        $body_vitals       = BodyVital::select('id', 'name')->get();
        $tpa_account       = MasterType::where('type_code', 'TPA')->first();
        $accounts          = Account::select('id', 'name')->where('account_type_id', $tpa_account->id)->get();
        $symptom_types     = SymptomClassification::select('id', 'symptoms_type')->where('status', 1)->get();
        $symptom_title     = Symptom::select('id','symptoms_title')->where('status',1)->get();
        $refferal_partner  = MasterType::where('type_code', 'REFERRAL_PARTNER')->first();
        $refferal_accounts = Account::select('id', 'name')->where('account_type_id', $refferal_partner->id)->get();
        $bed_groups        = BedGroup::select('id', 'name')->where('is_active', 1)->get();
        $beds              = Bed::select('id', 'name','room_id')->where('status', '1')->where('bed_available', '1')->get();
        $opdBooking        = OpdBookings::with('getOpdBookingDetails')
        ->with('getPatient')
        ->with('getDoctorAccount')
        ->find($id);

        $company_address = \Session::get('company_data');
        $countryCode     = $company_address['country_code'] ?? 'us';

        return view('ipd-admissions.create', compact('sources', 'accounts', 'body_vitals', 'refferal_accounts', 'symptom_types', 'bed_groups', 'beds','opdBooking','countryCode','symptom_title'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            // 'ipd_file_no' => 'required|unique:ipd_admissions,patient_id,NULL,id,deleted_at,NULL',
            'ipd_file_no' => 'nullable|unique:ipd_admissions,ipd_file_no,NULL,id,deleted_at,NULL',
            'patient_id'  => 'required|unique:ipd_admissions,patient_id,NULL,id,deleted_at,NULL',
            'doctor_id'   => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->getMessageBag()->first())->withInput();
        }

        $data = $request->except([
            '_token',
            '_method',
            'patient_id',
            'doctor_id',
            'admission_date_time',
            'ipd_file_no',
            'patient_source_id',
            'referal_from',
            'casualty',
            'tpa_account_id',
            'note',
            'previous_medical_issue',
            'symptom_id',
            'referral_partner_id',
            'bed_group_id',
            'bed_no_id',
            'opd_booking_id'
        ]);

        $ipd_file_no = $request->ipd_file_no;

        if (empty($request->ipd_file_no)) {
            $ipd_file_no = $this->generateUniqueIPDFileNo();
        }

        $voucher_type = VoucherType::with('voucherSeries')->where('code', 'IPD_ADMISSION')->first();

        # last IPD no count
        $last_admission_no_count = IpdAdmission::select('last_ipd_no_count')->orderBy('created_at', 'DESC')->first();
        $last_count              = isset($last_admission_no_count) && isset($last_admission_no_count->last_ipd_no_count) ? $last_admission_no_count->last_ipd_no_count + 1 : ($voucher_type->voucherSeries->start_from+1 ?? 1);

        $main_invoice_no = $this->generateCode(
            $last_count,
            ($voucher_type->voucherSeries->prefix ?? 'IPD'),
            ($voucher_type->voucherSeries->postfix ?? ''),
            ($voucher_type->voucherSeries->separator ?? '-'),
            ($voucher_type->voucherSeries->length ?? 5)
        );

        $admission_date = \DateTime::createFromFormat('Y-m-d H:i', $request->admission_date_time);

        $admission = [
            'note'                  => $request->note,
            'casualty'              => $request->casualty,
            'referal_from'          => $request->referal_from,
            'patient_source_id'     => $request->patient_source_id,
            'ipd_file_no'           => $ipd_file_no,
            'patient_id'            => $request->patient_id,
            'tpa_account_id'        => $request->tpa_account_id,
            'ipd_admission_no'      => $main_invoice_no,
            'last_ipd_no_count'     => $last_count,
            'previous_medical_issue'=> $request->previous_medical_issue,
            'referral_partner_id'   => $request->referral_partner_id,
            'admission_date_time'   => $request->admission_date_time != '' ? $admission_date->format('Y-m-d H:i:s') : '',
            'created_by'            => \Auth::user()->id ,
        ];
        

        $ipd_admission = IpdAdmission::create($admission);

        // Symptoms Details Store in Separate Table
        if ($request->symptom_id != '') {
            $symptoms = [
                'symptom_id'           => $request->symptom_id,
                'ipd_admission_id'     => $ipd_admission->id,
                'symptom_date_time'    => date('Y-m-d H:i:s'),
            ];

            IpdSymptomHistory::create($symptoms);
        }

        // Viatls Details Store in Separate Table
        foreach ($data as $key => $value) {

            if($value != ''){
                $vital_history = [
                    'ipd_admission_id'    => $ipd_admission->id,
                    'body_vital_id'       => $key,
                    'body_vital_value'    => $value,
                    'vitals_date_time'    => date('Y-m-d H:i:s'),
                ];

                IpdBodyVitalHistory::create($vital_history);
            }
        }

        // Bed Details Store in Separate Table
        if ($request->bed_group_id != '' || $request->bed_no_id != '') {
            $beds = [
                'ipd_admission_id'      => $ipd_admission->id,
                'bed_group_id'          => $request->bed_group_id,
                'bed_no_id'             => $request->bed_no_id,
                'check_in_date_time'    => $request->admission_date_time != '' ? $admission_date->format('Y-m-d H:i:s') : '',
            ];

            IpdBedHistory::create($beds);
        }


        // Consultant Details Store in Separate Table
        $isFirst = true;

        if ($request->doctor_id != ''  ) {
            $consultant = [
                'ipd_admission_id'      => $ipd_admission->id,
                'doctor_id'             => $request->doctor_id,
                'is_main_dr'            => $isFirst ? 1 : 0,
                'applied_date_time'     => $request->admission_date_time != '' ? $admission_date->format('Y-m-d H:i:s') : '',
                'instruction_date'      => $request->admission_date_time != '' ? $admission_date->format('Y-m-d H:i:s') : '',
            ];

            IpdConsultantHistory::create($consultant);

            $isFirst = false;
        }

        // Bed Availability Update in Beds Table
        if ($request->bed_no_id != '') {
            Bed::find($request->bed_no_id)->update(['bed_available' => 0, 'ipd_admission_no' => $ipd_admission->id]);
        }

        if(isset($request->opd_booking_id))
        {
            $opdBooking =  OpdBookings::find($request->opd_booking_id);
            $opdBooking->update(['ipd_convert_status'=>'Done']);
        }



        return redirect()->route('ipd-admissions.index')->with('success','Admission was created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $sale_invoices = IpdAdmission::select([
            'ipd_admissions.id',
            'sale_invoices.id as sale_invoices_name',
            'sale_returns.id as sale_returns_id'
            ]) ->leftjoin('sale_invoices', function ($join) {
                $join->on('ipd_admissions.patient_id', '=', 'sale_invoices.patient_id')
                    ->where('sale_invoices.invoice_type', '=', 'ipd-billings');
            })
            ->leftjoin('sale_returns', 'sale_invoices.id', 'sale_returns.voucher_type_id')
            ->where('ipd_admissions.id', $id)->first();

        $admission = IpdAdmission::find($id);

        if (!$admission) {
            abort(404);
        }

        // PATIENT NAME
        $patient = Account::where('id', $admission->patient_id)->with('account_contact','account_images', 'account_title')->first();

        // PATIENT SOURCE
        $patient_source = EnquirySource::select('enquiry_source_name')->where('id', $admission->patient_source_id)->first();

        // REFFERAL PARTNER
        $refferal_partner = Account::select('name')->where('account_type_id', $admission->referral_partner_id)->first();

        // TPA ACCOUNT
        $tpa_account = Account::select('name')->where('account_type_id', $admission->tpa_account_id)->first();

        $marital_status  = MaritalStatus::where('id', $patient->account_contact->marital_status)->first();
        $blood_group     = BloodGroup::where('id', $patient->account_contact->blood_group_id)->first();

        $dischargeExists = IpdDischarge::where('ipd_admission_id', $id)->exists();
    
        return view('ipd-admissions.show', compact(
            'id',
            'admission',
            'patient',
            'patient_source',
            'refferal_partner',
            'tpa_account',
            'marital_status',
            'blood_group',
            'dischargeExists',
            'sale_invoices'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $data = IpdAdmission::find($id);

        // PATIENT DETAILS
        $patient = Account::select('name', 'account_title_id', 'id', 'code')->where('id', $data->patient_id)
            ->with('account_contact', 'account_title')->first();

        // CONSULTANT DETAILS
        $consultant = IpdConsultantHistory::where('ipd_admission_id', $id)->with('consultants')->first();

        // ADMISSION DETAILS
        $sources           = EnquirySource::select('id', 'enquiry_source_name')->get();
        $tpa_account       = MasterType::where('type_code', 'TPA')->first();
        $accounts          = Account::select('id', 'name')->where('account_type_id', $tpa_account->id)->get();
        $refferal_partner  = MasterType::where('type_code', 'REFERRAL_PARTNER')->first();
        $refferal_accounts = Account::select('id', 'name')->where('account_type_id', $refferal_partner->id)->get();

        return view('ipd-admissions.edit', compact('data', 'patient', 'consultant', 'sources', 'accounts', 'refferal_accounts'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = \Validator::make($request->all(), [
            'patient_id' => 'required|unique:ipd_admissions,patient_id,'.$id.',id,deleted_at,NULL',
            'doctor_id'  => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->getMessageBag()->first())->withInput();
        }

        $data = $request->except([
            '_token',
            '_method',
            'doctor_id',
            'admission_date_time',
        ]);

        $data['admission_date_time']    = $request->admission_date_time != '' ? date('Y-m-d H:i:s', strtotime($request->admission_date_time)) : '';

        $ipd_admission = IpdAdmission::find($id);

        $ipd_admission->update($data);

        if (is_array($request->doctor_id)) {
            // Consultant Details Update
            $consultantHistory = IpdConsultantHistory::where('ipd_admission_id', $id)->whereNotIn('doctor_id', $request->doctor_id)->delete();

            foreach ($request->doctor_id as $key => $doctor_id) {

                IpdConsultantHistory::updateOrCreate([
                    'ipd_admission_id'  => $id,
                    'doctor_id'         => $doctor_id,
                ], [
                    'is_main_dr'        => ($key == 0) ? 1 : 0,
                    'applied_date_time' => $request->admission_date_time != '' ? date('Y-m-d H:i:s', strtotime($request->admission_date_time)) : '',
                    'instruction_date'  => $request->admission_date_time != '' ?date('Y-m-d H:i:s', strtotime($request->admission_date_time)) : '',
                ]);
            }
        } else {
            $consultantHistory = IpdConsultantHistory::where('ipd_admission_id', $id)->whereNot('doctor_id', $request->doctor_id)->delete();

            IpdConsultantHistory::updateOrCreate([
                'ipd_admission_id'  => $id,
                'doctor_id'         => $request->doctor_id,
            ], [
                'is_main_dr'        => 1,
                'applied_date_time' => $request->admission_date_time != '' ? date('Y-m-d H:i:s', strtotime($request->admission_date_time)) : '',
                'instruction_date'  => $request->admission_date_time != '' ?date('Y-m-d H:i:s', strtotime($request->admission_date_time)) : '',
            ]);
        }

        return redirect()->route('ipd-admissions.index')->with('success','Admission was updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $IPD = IpdAdmission::find($request->id);
        $patient_id = $IPD->patient_id;
        $IPD->delete();


        // Bed Availability Update in Beds Table and Beds history delete
        $beds = IpdBedHistory::where('ipd_admission_id', $request->id)->get();

        foreach ($beds as $bed) {
            if ($bed->bed_no_id != null) {
                Bed::find($bed->bed_no_id)->update(['bed_available' => 1, 'ipd_admission_no' => null]);
                IpdBedHistory::find($bed->id)->delete();
            }
        }

        // Viatls history delete
        IpdBodyVitalHistory::where('ipd_admission_id', $request->id)->delete();

        // Symptoms history delete
        IpdSymptomHistory::where('ipd_admission_id', $request->id)->delete();

        // Consultants history delete
        IpdConsultantHistory::where('ipd_admission_id', $request->id)->delete();

        /*************All Billings Delete****************/
        $saleInvoice = SaleInvoice::where('patient_id', $patient_id)
        ->where('invoice_type', 'ipd-billings')
        ->first();
        $saleInvoiceId = $saleInvoice->id??'';
        if(isset($saleInvoice ))
        {

            if (isset($saleInvoiceId)) {
                $saleInvoice->update(['deleted_by' => \Auth::user()->id]);
                SaleInvoiceDetail::where('sale_invoice_id', $saleInvoiceId)->delete();
                SaleInvoiceBatch::where('invoice_id', $saleInvoiceId)->delete();
            }

            $acReceiptData = AcReceipt::where([
                'voucher_id'     => $saleInvoiceId,
                'module_code'    => 'SALES'
            ])->first();

            if ($acReceiptData) {
                $accountTransactionData = AccountTransaction::where([
                    'voucher_id'        =>  $acReceiptData->id,
                    'module_code'       =>  'SALES',
                    'transaction_type'  =>  'CREDIT'
                ])->first();
                if ($acReceiptData) {
                    $voucherCollectionData = VoucherCollection::where('money_receipt_id', $acReceiptData->id)->first();
                    VoucherCollectionDetail::where([
                        'voucher_collection_id' =>  $voucherCollectionData->id,
                    ])->delete();
                    AcReceiptDetail::where([
                        'voucher_id'             => $acReceiptData->id,
                        'voucher_type'           => 'SALES'
                    ])->delete();
                    $voucherCollectionData->update(['deleted_by' => \Auth::user()->id]);
                    $voucherCollectionData->delete();
                    $acReceiptData->update(['deleted_by' => \Auth::user()->id]);
                }

                AccountTransaction::where([
                    'voucher_id'        =>  $acReceiptData->id,
                    'module_code'      =>  'SALES'
                ])->delete();
                $acReceiptData->delete();
            }
            $saleInvoice->delete();
        }



        $Redirect = 'ipd-admissions';

        return response()->json([
            'success' => true,
            'message' => ['Deleted successfully'],
            'data'    => [
                'redirect' => $Redirect,
            ]
        ]);
    }

    public function Consultant($id)
    {
        $consultants = IpdConsultantHistory::select('id', 'is_main_dr', 'applied_date_time', 'doctor_id')->where('ipd_admission_id', $id)->with('consultants')->get();

        return view('ipd-admission-consultants.table', compact('consultants'));
    }

    public function createConsultant(Request $request)
    {
        $ipd_id = $request->id;

        return view('ipd-admission-consultants.create', compact('ipd_id'));
    }

    public function storeConsultant(Request $request, $id)
    {
        $validationFailures = [];

        foreach ($request->doctor_id as $doctorId) {
            $validator = \Validator::make(['doctor_id' => $doctorId], [
                'doctor_id' => 'unique:ipd_consultant_histories,doctor_id,NULL,id,ipd_admission_id,' . $id,
            ]);

            if ($validator->fails()) {
                $validationFailures[] = $validator->getMessageBag()->first();
            }
        }

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->getMessageBag()->first(),
                    'data'    => []
                ]);
            }
        }

        // Consultant Details Store in Separate Table
        foreach ($request->doctor_id as $doctor_id) {
            $consultant['ipd_admission_id']  = $id;
            $consultant['doctor_id']         = $doctor_id;
            $consultant['is_main_dr']        = 0;
            $consultant['applied_date_time'] = date('Y-m-d H:i:s');
            $consultant['instruction_date']  = date('Y-m-d H:i:s');

            IpdConsultantHistory::create($consultant);
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Consultants were added successfully.',
                'data'    => ''
            ]);
        }
    }

    public function destroyConsultant(Request $request)
    {
        IpdConsultantHistory::find($request->id)->delete();

        return response()->json([
            'success' => true,
            'message' => ['Deleted successfully'],
            'data'    => [
            'redirect' => '',
            ]
        ]);
    }

    public function Bed($id)
    {
        $ipd_patient_id = IpdAdmission::select('id','patient_id')->where('id',$id)->first();

        $bed_histories = IpdBedHistory::where('ipd_admission_id', $id)->with('getBed', 'getBedGroup')->get();

        return view('ipd-admission-beds.table', compact('bed_histories','ipd_patient_id','id'));
    }

    public function createBed(Request $request)
    {
        $ipd_id = $request->id;
        $beds   = Bed::select('beds.id', 'beds.name','beds.room_id','bed_groups.name as group_name','bed_groups.code as group_code')
            ->leftjoin('bed_groups', 'bed_groups.id' , '=' , 'beds.bed_group_id')
            ->where('status', '1')
            ->where('bed_available', '1')
            ->get()
            ->groupBy('group_code')
            ->map(function ($beds, $groupCode) {
                return [
                    'group' => $beds[0]->group_name,
                    'beds'  => $beds,
                ];
            });

        return view('ipd-admission-beds.assign-bed-step-1', compact('ipd_id', 'beds'));
    }

    public function createBedStep2(Request $request)
    {
        $ipd_id    = $request->id;
        $bed_no_id = $request->bed_id;

        return view('ipd-admission-beds.create', compact('ipd_id', 'bed_no_id'));
    }

    public function storeBed(Request $request, $id)
    {
        $validator = \Validator::make($request->all(), [
            'bed_no_id' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->getMessageBag()->first())->withInput();
        }

        $data = $request->except([
            '_token',
            '_method',
        ]);

        $data['ipd_admission_id']   = $id;
        $data['check_in_date_time'] = $request->check_in_date_time != '' ? date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $request->check_in_date_time))) : '';


        IpdBedHistory::create($data);

        // Bed Availability Update in Beds Table
        $ipd = IpdAdmission::select('ipd_admission_no')->find($id);

        if ($request->bed_no_id != '') {
            Bed::find($request->bed_no_id)->update(['bed_available' => 0, 'ipd_admission_no' => $ipd->ipd_admission_no]);
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Bed added successfully.',
                'data'    => ''
            ]);
        }
    }

    public function editBed($id)
    {
        $data       = IpdBedHistory::find($id);
        $bed_groups = BedGroup::select('id', 'name')->where('is_active', 1)->get();
        $beds       = Bed::select('id', 'name','room_id')->where('status', '1')->where('bed_available', '1')->where('bed_group_id',$data->bed_group_id)->orWhere('id', $data->bed_no_id)->get();

        return view('ipd-admission-beds.edit', compact('data', 'beds', 'bed_groups'));
    }

    public function updateBed(Request $request, $id)
    {
        $validator = \Validator::make($request->all(), [
            'bed_no_id' => 'required|unique:ipd_bed_histories,bed_no_id,'.$id.',id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->getMessageBag()->first())->withInput();
        }

        $data = $request->except([
            '_token',
            '_method',
        ]);

        $data['check_in_date_time'] = $request->check_in_date_time != '' ? date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $request->check_in_date_time))) : '';
        $data['check_out_date_time'] = $request->check_out_date_time != '' ? date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $request->check_out_date_time))) : '';


        $bed = IpdBedHistory::find($id);

        // Bed Availability Update in Beds Table
        $ipd = IpdAdmission::select('ipd_admission_no')->find($bed->ipd_admission_id);

        if ($bed->bed_no_id != $request->bed_no_id) {
            if ($bed->bed_no_id != null) {
                Bed::find($bed->bed_no_id)->update(['bed_available' => 1, 'ipd_admission_no' => null]);
            }
            if ($request->bed_no_id != null) {
                Bed::find($request->bed_no_id)->update(['bed_available' => 0, 'ipd_admission_no' => $ipd->ipd_admission_no]);
            }
        }

        $bed->update($data);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Bed updated successfully.',
                'data'    => ''
            ]);
        }
    }

    public function destroyBed(Request $request)
    {
        $bed = IpdBedHistory::find($request->id);

        if ($bed->bed_no_id != '') {
            Bed::find($bed->bed_no_id)->update(['bed_available' => 1, 'ipd_admission_no' => null]);
        }

        $bed->delete();

        $Redirect = 'ipd-admissions';
            
            return response()->json([
            'success' => true,
            'message' => ['Deleted successfully'],
            'data'    => [
            'redirect' => $Redirect,
            ]
        ]);
    }

    public function bedsAllotmentStatus(Request $request, $id)
    {
        if ($request->ajax()) {
            $data   = array('allotment_status' => $request->status );
            $Update = IpdBedHistory::where('id', '=', $request->id)->update($data);

            if($Update){
                return response()->json([
                    'success'=>true,
                    'message'=>['Allotment status successfully change'],
                    'data'=>[
                       'redirect'=>'/users/',
                       'reload'=>true,
                    ]
                ]);
            } else {
                return response()->json([
                   'success'=>false,
                   'message'=>['Error for change status'],
                   'data'=>[
                       'redirect'=>'',
                   ]
                ]);
            }
        }
    }

    public function symptom($id)
    {
        $symptoms = IpdSymptomHistory::select('id', 'symptom_id', 'symptom_date_time')->where('ipd_admission_id', $id)->with('getSymptomTitle')->get();

        return view('ipd-admission-symptoms.table', compact('symptoms'));
    }

    public function createSymptom($id)
    {
        $symptom_types = SymptomClassification::select('id', 'symptoms_type')->where('status', 1)->get();

        return view('ipd-admission-symptoms.create', compact('symptom_types', 'id'));
    }

    public function StoreSymptom(Request $request, $id)
    {
        $data = $request->except([
            '_token',
            '_method',
        ]);

        $validator = \Validator::make($request->all(), [
            'symptom_id' => 'required|unique:ipd_symptom_histories,symptom_id,NULL,id,ipd_admission_id,' . $id,
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->getMessageBag()->first(),
                    'data'    => []
                ]);
            }
        }

        $data['symptom_date_time'] = date('Y-m-d H:i:s');
        $data['ipd_admission_id'] = $id;

        IpdSymptomHistory::create($data);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Symptom added successfully.',
                'data'    => ''
            ]);
        }
    }

    public function destroySymptom(Request $request)
    {
        IpdSymptomHistory::find($request->id)->delete();

        return response()->json([
            'success' => true,
            'message' => ['Deleted successfully']
        ]);
    }

    public function getSymptomsTitle(Request $request)
    {
        $symptom_type_id = $request->symptom_type;
        $symtom_id       = $request->symtom_id;

        if ($symptom_type_id) {

            $symptoms = Symptom::select('id', 'symptoms_title')
                ->where('type', $symptom_type_id)
                ->where('status', 1)
                ->get();

            $data = '';

            foreach ($symptoms as $symptom) {
                $selected = ($symptom->id == $symtom_id) ? 'selected' : '';
                $data .= '<option ' . $selected . ' value="' . $symptom->id . '">' . $symptom->symptoms_title . '</option>';
            }

            return response()->json([
                'success' => true,
                'data'    => $data
            ]);
        } else {
            return response()->json([
                'success' => true,
                'data'    => '',
            ]);
        }
    }

    public function getSymptomsDescription(Request $request)
    {
        $symptom_id = $request->symptom_title;

        if ($symptom_id) {

            $symptoms = Symptom::select('description')
                ->where('id', $symptom_id)
                ->where('status', 1)
                ->first();

            return response()->json([
                'success' => true,
                'data'    => $symptoms->description,
            ]);
        } else {
            return response()->json([
                'success' => true,
                'data'    => '',
            ]);
        }
    }

    public function patientDischargeEdit($id)
    {

        $discharge_types = DischargeType::select('id', 'name', 'code')->where('status', 1)->get();
        $admission       = IpdAdmission::where('id', $id)->with('contacts', 'consultantHistory')->first();
        $tpa_account     = Account::select('name')->where('account_type_id', $admission->tpa_account_id)->first();
        $blood_group     = BloodGroup::where('id', $admission->contacts->account_contact->blood_group_id)->first();
        $data            = IpdDischarge::where('ipd_admission_id', $id)->first();
        $settings        = IpdDischargeTypeSetting::select(
                'ipd_discharge_type_settings.*',
                'ipd_discharge_details.value',
                'discharge_types.code',
            )
            ->leftjoin('discharge_types', 'discharge_types.id','ipd_discharge_type_settings.discharge_type_id')
            ->leftjoin('ipd_discharge_details', function($join) use ($id, $data) {
                $join->on('ipd_discharge_details.ipd_discharge_type_settings_id', 'ipd_discharge_type_settings.id')
                ->when($data && isset($data->id), function($query) use ($data) {
                    $query->where('ipd_discharge_details.ipd_discharge_id', $data->id);
                });
            })
            ->get();

        // $data                = IpdAdmissionDischarged::where('ipd_admission_id', $id)->first();
        // $referral_discharged = IpdAdmissionReferralDischarged::where('ipd_admission_id', $id)->first();
        // $death_discharged    = IpdAdmissionDeathDischarged::where('ipd_admission_id', $id)->first();


        return view('ipd-admissions.patient-discharge', compact(
            'id',
            'discharge_types',
            'admission',
            'tpa_account',
            'blood_group',
            'data',
            'settings',
    
        ));

    }

    public function patientDischargeUpdate(Request $request, $id)
    {
        $validator = \Validator::make($request->all(), [
            'discharge_datetime' => 'required'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->getMessageBag()->first())->withInput();
        }

        $data = [
            'discharge_type_code'    => $request->discharge_type_code,
            'discharge_datetime'     => $this->dateFormat($request->discharge_datetime, 'm/d/Y'),
            'next_follow_up_date'    => $this->dateFormat($request->next_follow_up_date,'m/d/Y'),
            'referral_date_time'     => $this->dateFormat($request->referral_date_time, 'm/d/Y'),
            'death_date_time'        => $this->dateFormat($request->death_date_time, 'm/d/Y'),
            'condition_of_discharge' => $request->condition_of_discharge,
            'special_note'           => $request->special_note,
            'referral_hospital_name' => $request->referral_hospital_name,
            'reason_for_referral'    => $request->reason_for_referral,
            'family_contact'         => $request->family_contact,
            'created_by_id'             => \Auth::user()->id,
        ];

        $discharge = IpdDischarge::updateOrCreate([
            'ipd_admission_id' => $id,
        ], $data);

        if (!empty($request->details)) {
            foreach ($request->details as $key => $value) {
                if (!empty($value)) {
                    IpdDischargeDetail::updateOrCreate([
                        'ipd_discharge_id'               => $discharge->id,
                        'ipd_discharge_type_settings_id' => $key
                    ], [
                        'value' => $value
                    ]);
                }
            }
        }

        $checkCheckout = IpdBedHistory::where('ipd_admission_id',$id)->update([
            'check_out_date_time' => date('Y-m-d'),
        ]);

        $ipd_admission_no = IpdAdmission::select('id')->where('id',$id)->first();
        $available_beds   = Bed::where('ipd_admission_no', $ipd_admission_no->id)->get();

        if ($available_beds->count() > 0) {
            $patient_list = DB::table('ipd_admissions')
            ->leftJoin('ipd_discharges', 'ipd_admissions.id', '=', 'ipd_discharges.ipd_admission_id')
            ->leftJoin('ipd_bed_histories', 'ipd_admissions.id', '=', 'ipd_bed_histories.ipd_admission_id')
            ->whereNull('ipd_discharges.ipd_admission_id')
            ->whereNull('ipd_bed_histories.ipd_admission_id')
            ->whereNotNull('ipd_admissions.id')
            ->get()
            ->toArray();

            foreach ($available_beds as $key => $bed) {

                $available_beds = $bed->update
                ([
                    'bed_available'    => 1,
                    'ipd_admission_no' => null,
                ]);

                    if (isset($patient_list[$key])){
                        $bed_id = $bed->id;
                        $did = Bed::where('id', $bed_id)
                        ->update([
                            'waiting_ipd_admission_id' => 0,
                            'bed_available'            => 0,
                            'ipd_admission_no'         => $patient_list[$key]->ipd_admission_no,
                        ]);
                        $history = IpdBedHistory::create([
                            'ipd_admission_id' => $patient_list[$key]->ipd_admission_no,
                            'bed_no_id'        => $bed_id,
                            'bed_group_id'     => null,
                        ]);
                    }
            }
        }
      

        $validator = \Validator::make($request->all(), [
            'discharge_datetime' => 'required'
        ]);

        if (($request->referral_date_time != '') && ($request->referral_hospital_name != '')) {
            if ($request->discharge_type_code == 'REFERRAL') {

                IpdDischarge::updateOrCreate([
                    'ipd_admission_id' => $id,
                ], [
                    'referral_date_time'     => $this->dateFormat($request->referral_date_time, 'm/d/Y'),
                    'referral_hospital_name' => $request->referral_hospital_name,
                    'reason_for_referral'    => $request->reason_for_referral
                ]);
            } else {
                IpdDischarge::where('ipd_admission_id', $id)->delete();
            }
        }

        if (($request->death_date_time != '') && ($request->guardian_name != '')) {
            if ($request->discharge_type_code == 'DEATH') {

                if ($request->death_file_attachment != '') {

                    $death_discharged = IpdDischarge::where('ipd_admission_id', $id)->first();
                    $old_image = $death_discharged->file_attachment ?? '';

                    $path     = "death_discharged";
                    $response = uploadImage($path,$request->death_file_attachment,$old_image);

                    if ($response['status'] == true) {
                        $death_discharged_file_path = $response['file_name'];

                        IpdDischarge::updateOrCreate([
                            'ipd_admission_id' => $id,
                        ], [
                            'death_date_time' => $this->dateFormat($request->death_date_time, 'm/d/Y'),
                            'guardian_name'   => $request->guardian_name,
                            'death_report'    => $request->death_report,
                            'file_attachment' => $death_discharged_file_path
                        ]);
                    } else {
                        if ($request->ajax()) {
                            return response()->json([
                                'success' => false,
                                'message' => $response['message'],
                                'data'    => []
                            ]);
                        }
                        return redirect()->back()->with('error', $response['message']);
                    }
                }

                IpdDischarge::updateOrCreate([
                    'ipd_admission_id' => $id,
                ], [
                    'death_date_time' => $this->dateFormat($request->death_date_time, 'm/d/Y'),
                    'guardian_name'   => $request->guardian_name,
                    'death_report'    => $request->death_report
                ]);
            } 


            else {
                $death_discharged = IpdDischarge::where('ipd_admission_id', $id)->first();

                if (isset($death_discharged) && ($death_discharged->file_attachment != '')) {
                    deleteImage($death_discharged->file_attachment);
                    $death_discharged->delete();
                }
            }
        }

    
        return redirect()->route('ipd-admissions.dischargestatement', $id)->with('success','Patient was discharged successfully');
    }
    public function dischargeStatement(Request $request, $id){
        $products = Product::select('products.*')
        ->leftjoin('sale_invoice_details','sale_invoice_details.product_id','products.id')
        ->leftjoin('sale_invoices','sale_invoices.id','sale_invoice_details.sale_invoice_id')
        ->leftjoin('ipd_admissions','ipd_admissions.sale_invoice_id','sale_invoices.id')
        ->where('ipd_admissions.id', $id)
        ->get();

        $data  = IpdDischarge::where('ipd_admission_id', $id)->select('discharge_types.*','ipd_discharges.*')
            ->leftjoin('discharge_types', 'discharge_types.code','ipd_discharges.discharge_type_code')
            ->first();
        $admission = IpdAdmission::where('id', $id)->with('contacts', 'consultantHistory')->first();
        $settings = IpdDischargeTypeSetting::select(
                'ipd_discharge_type_settings.*',
                'ipd_discharge_details.value',
                'discharge_types.code',
            )
            ->leftjoin('discharge_types', 'discharge_types.id','ipd_discharge_type_settings.discharge_type_id')
            ->leftjoin('ipd_discharge_details', function($join) use ($id, $data) {
                $join->on('ipd_discharge_details.ipd_discharge_type_settings_id', 'ipd_discharge_type_settings.id')
                ->when($data && isset($data->id), function($query) use ($data) {
                    $query->where('ipd_discharge_details.ipd_discharge_id', $data->id);
                });
            })
            ->where('discharge_types.code',$data->discharge_type_code)
            ->get();

        $ipd_id = IpdAdmission::select('id')->where('id', '=', $id)->first();
        $sale_invoice = SaleInvoice::select('sale_invoices.*',
        DB::raw('beds.name as bed_name', 'ipd_admission_discharged.discharge_date_time as discharge_date', 'ipd_admissions.*', 'accounts.name as account_name'))
        ->with([
            'getSaleInvoiceDetails',
            'getSaleInvoiceDelivery',
            'getPatient',
            'getPaymentTerm',
            'getCompany',
            'getBatchs'
        ])
        ->leftJoin('ipd_admissions', 'ipd_admissions.patient_id', '=', 'sale_invoices.patient_id')
        ->leftJoin('ipd_bed_histories', 'ipd_admissions.id', '=', 'ipd_bed_histories.ipd_admission_id')
        ->leftJoin('beds', 'ipd_bed_histories.bed_no_id', '=', 'beds.id')
        ->leftJoin('ipd_admission_discharged', 'ipd_admissions.id', '=', 'ipd_admission_discharged.ipd_admission_id')
        ->leftJoin('accounts', 'ipd_admissions.tpa_account_id', '=', 'accounts.id')
        ->leftJoin('account_contacts', 'account_contacts.account_id', '=', 'accounts.id')
        ->where('ipd_admissions.id', $id)
        ->first();


        $voucher_type = VoucherType::with('voucherSeries')->where('code', 'IPD_INVOICE')->first();

       
        $transactionSummery = $this->TransactionSummery($id, 'IPD_INVOICE');

        return view('ipd-admissions.discharge-statement', compact('products','data','admission','settings','sale_invoice', 'voucher_type', 'transactionSummery', 'ipd_id'));

    }

    public function pdf(Request $request, $id)
    {
       

        $discharge_types = DischargeType::select('id','name','code')->where('status', 1)->get();
        $admission       = IpdAdmission::where('id', $id)->with('contacts', 'consultantHistory')->first();
        $tpa_account     = Account::select('name')->where('account_type_id', $admission->tpa_account_id)->first();
        $blood_group     = BloodGroup::where('id', $admission->contacts->account_contact->blood_group_id)->first();
        $data            = IpdDischarge::where('ipd_admission_id', $id)->select('discharge_types.*','ipd_discharges.*')

            ->leftjoin('discharge_types', 'discharge_types.code','ipd_discharges.discharge_type_code')
            ->first();


        $settings = IpdDischargeTypeSetting::select(
                'ipd_discharge_type_settings.*',
                'ipd_discharge_details.value',
                'discharge_types.code',
            )
            ->leftjoin('discharge_types', 'discharge_types.id','ipd_discharge_type_settings.discharge_type_id')
            ->leftjoin('ipd_discharge_details', function($join) use ($id, $data) {
                $join->on('ipd_discharge_details.ipd_discharge_type_settings_id', 'ipd_discharge_type_settings.id')
                ->when($data && isset($data->id), function($query) use ($data) {
                    $query->where('ipd_discharge_details.ipd_discharge_id', $data->id);
                });
            })
            ->where('discharge_types.code',$data->discharge_type_code)
            ->get();
           

        $pdf = PDF::loadView('ipd-admissions.discharge_pdf', [
                'id' => $id,
                'discharge_types' => $discharge_types,
                'admission' => $admission,
                'tpa_account' => $tpa_account,
                'blood_group' => $blood_group,
                'data' => $data,
                'settings' => $settings,
            ]);


            $file = 'project' . time() . '.pdf';
            return $pdf->stream(); 

        return view('ipd-admissions.discharge_pdf', compact(
            'id',
            'discharge_types',
            'admission',
            'tpa_account',
            'blood_group',
            'data',
            'settings',
    
        ));


    }

    public function dateFormat($dateString, $format)
    {
        try {
            if (!empty($dateString)) {
                $dateTime = Carbon::createFromFormat($format, $dateString);

                return $dateTime->format('Y-m-d H:i');
            } else {
                return null;
            }
        } catch (InvalidFormatException $e) {
            Log::error('Date format error: ' . $e->getMessage() . ' Input date: ' . $dateString);

            return null; // Or any other appropriate action
        }
    }
    


    public function bodyVitals($id)
    {
        $vital_history      = BodyVital::with('get_vital_histories')->withCount('get_vital_histories')->where('status', 1)->get();
        $maxCountRecord     = $vital_history->max('get_vital_histories_count');
        $maxCountRecord     = $vital_history->where('get_vital_histories_count', $maxCountRecord)->first();
        $topcountvitals     = $maxCountRecord->get_vital_histories_coffunt ?? 0;
        $body_vital_columns = BodyVital::select('name')->where('status', 1)->get();

        return view('ipd-admission-body-vitals.table', compact('body_vital_columns', 'topcountvitals', 'vital_history'));
    }

    public function createBodyVitals(Request $request, $id)
    {
        $body_vitals = BodyVital::select('id', 'name')->where('status', 1)->get();

        return view('ipd-admission-body-vitals.create', compact('id', 'body_vitals'));
    }

    public function storeBodyVitals(Request $request, $id)
    {
        $data = $request->except([
            '_token',
            '_method',
        ]);

        $batch    = IpdBodyVitalHistory::max('batch_no') ?? 0;
        $batch_no = $batch+1;

        foreach ($data as $key => $value) {
            $vital_history = [];

            $vital_history['ipd_admission_id'] = $id;
            $vital_history['body_vital_id']    = $key;
            $vital_history['body_vital_value'] = ($value != '') ? $value : null;
            $vital_history['batch_no']         = $batch_no;
            $vital_history['vitals_date_time'] = date('Y-m-d H:i:s');

            IpdBodyVitalHistory::create($vital_history);
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Body Vitals are added successfully.',
                'data'    => ''
            ]);
        }
    }

    public function editBodyVitals($id)
    {
        $body_vitals = BodyVital::select('id', 'name')
        ->where('status', 1)
        ->with(['edit_vital_histories' => function ($query) use ($id) {
            $query->where('batch_no', $id);
        }])
        ->get();

        return view('ipd-admission-body-vitals.edit', compact('body_vitals', 'id'));
    }

    public function updateBodyVitals(Request $request, $id)
    {
        $data = $request->except([
            '_token',
            '_method',
        ]);

        foreach ($data as $key => $value) {

            IpdBodyVitalHistory::updateOrCreate([
                'batch_no' => $id,
                'body_vital_id' => $key,
            ],[
                'body_vital_value' => $value
            ]);
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Body Vitals are updated successfully.',
                'data'    => ''
            ]);
        }
    }

    public function destroyBodyVitals(Request $request)
    {
        $bed = IpdBodyVitalHistory::where('batch_no', $request->id)->delete();

        $Redirect = 'ipd-admissions';

        return response()->json([
            'success' => true,
            'message' => ['Deleted successfully'],
            'data'    => [
                'redirect' => $Redirect,
            ]
        ]);
    }
    public function getBeds(Request $request)
    {
        $bed_group_id = $request->bed_group_id;
        $bed_no_id    = $request->bed_no_id ?? '';

        if ($bed_group_id) {

            $beds = Bed::select('id', 'name', 'room_id', 'bed_group_id')
            ->where([
                'bed_group_id'  => $bed_group_id,
                'status'        => '1',
                'bed_available' => 1
            ])
            ->when(!empty($bed_no_id) , function($query) use ($bed_no_id, $bed_group_id) {
                    $query->orWhere(function($query2) use ($bed_no_id, $bed_group_id) {
                        $query2->where(['bed_group_id' => $bed_group_id,'id' => $bed_no_id]);
                    });
                })
            ->with('getRoom')
            ->get();

            $data = '';
            foreach ($beds as $list) {
                $selected = ($list->id == $bed_no_id) ? 'selected' : '';
                $roomName = $list->getRoom->room_name ?? '';
                $floor = $list->getRoom->getFloor->name ?? '';
                $building = $list->getRoom->getBuilding->name ?? '';
                $data .= '<option ' . $selected . ' value="' . $list->id . '">' . $list->name . ' - ' . $roomName . ' - ' . $building . ' - ' . $floor . '</option>';
            }

            return response()->json([
                'success' => true,
                'data'    => $data
            ]);
        } else {
            return response()->json([
                'success' => true,
                'data'    => 'not working',
            ]);
        }
    }


    public function verifyChecklist(Request $request, $id)
    {
        $pending_history = IpdBedHistory::where(['ipd_admission_id' => $id])
            ->get();

        $IPD = $admission = IpdAdmission::find($id);
        $patient_id = $IPD->patient_id;

        $sale_invoice_status = SaleInvoice::getSaleInvoices([
                'sale_invoices.invoice_type' => 'ipd-billings',
                'sale_invoices.patient_id'   => $patient_id
            ], [], 1)->get();
        $total_reports   = $sale_invoice_status->count();
        $doneReports     = $sale_invoice_status->where('all_tests_reviewed', '=', 1)->count();
        $sampleCollected = $sale_invoice_status->where('sample_collection_status', '=', 1)->count();
        $updatedReports  = $sale_invoice_status->where('all_tests_updated', '1')->where('all_tests_recheck', '0')->count();

        // PATIENT NAME
        $patient = Account::where('id', $admission->patient_id)->with('account_contact','account_images', 'account_title')->first();

        // PATIENT SOURCE
        $patient_source = EnquirySource::select('enquiry_source_name')->where('id', $admission->patient_source_id)->first();

        // REFFERAL PARTNER
        $refferal_partner = Account::select('name')->where('account_type_id', $admission->referral_partner_id)->first();

        // TPA ACCOUNT
        $tpa_account = Account::select('name')->where('account_type_id', $admission->tpa_account_id)->first();

        $marital_status  = MaritalStatus::where('id', $patient->account_contact->marital_status)->first();
        $blood_group     = BloodGroup::where('id', $patient->account_contact->blood_group_id)->first();

        $saleInvoice = SaleInvoice::select('id')->where('patient_id', $patient_id)
            ->where('invoice_type', 'ipd-billings')
            ->first();

        $salereturn = SaleReturns::select('sale_returns.*', 'sale_invoices.voucher_type_invoice_no as reference_invoice_no')
            ->leftjoin('sale_invoices', 'sale_invoices.id', 'sale_returns.voucher_type_id')
            ->where('sale_returns.voucher_type_code', 'SALE_RETURN')
            ->where('sale_returns.voucher_type_id', $saleInvoice->id)
            ->with('getSaleInvoiceDetail')
            ->with('getRefferalLab')
            ->with('getCustomer')
            ->with('getOperator')
            ->with('getPaymentMode')
            ->get();

        return view('ipd-admissions.verify-checklist', compact(
            'pending_history',
            'admission',
            'total_reports',
            'sale_invoice_status',
            'doneReports',
            'sampleCollected',
            'updatedReports',
            'patient',
            'patient_source',
            'refferal_partner',
            'tpa_account',
            'marital_status',
            'blood_group',
            'salereturn',
        ));
    }

    public function generateUniqueIPDFileNo() {
        $pool = '0123456789';

        do {
            $testvar = substr(str_shuffle(str_repeat($pool, 5)), 0, 8);;
            $data = IpdAdmission::where('ipd_file_no', $testvar)->get();
        } while (count($data) > 0);
        return $testvar;
    }
}
