<?php

namespace App\Exports;

use App\Models\OpdBookings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Events\AfterSheet;
use App\Models\IpdAdmission; 
use App\Models\Company; 
use Carbon\Carbon;

class IpdBillingStatementExport implements FromCollection
{

    protected $data;

    public function __construct($data)
    {
        $this->data = collect($data);
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $data = $this->data;
       

        $records[] = [
            'Admmission No',
            'File No',
            'Claim No',
            'Admission Date Time',
            'Patient Id',
            'Patient Source',
            'Tpa Account',
            'Casualty',
            'Referal Form',
            'Note' ,
            'Referal Partner' ,
            'Previous Medical' ,
            'Sale Invoice' ,
        ];

   

        foreach ($data as $key => $admission) {
            $records[] =  [
           
            $admission['ipd_admission_no'] ,
            $admission['ipd_file_no'] ,
            $admission['claim_number'] ,
            \Illuminate\Support\Carbon::parse($admission['admission_date_time']),
            $admission['patient_id'] ,
            $admission['patient_source_id'] ,
            $admission['tpa_account_id'] ,
            $admission['casualty'] ,
            $admission['referal_from'] ,
            $admission['note'] ,
            $admission['referral_partner_id'] ,
            $admission['previous_medical_issue'] ,
            $admission['sale_invoice_id'] , 
            ];

          
        }

        return collect($records);
    }

    


   
}
