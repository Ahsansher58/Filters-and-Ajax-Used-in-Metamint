<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IpdAdmission extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function contacts()
    {
        return $this->belongsTo(Account::class, 'patient_id')->with('account_contact', 'account_title', 'company');
    }

    public function consultantHistory()
    {
        return $this->hasOne(IpdConsultantHistory::class, 'ipd_admission_id', 'id')->with('consultants');
    }
    public function getTpa()
    {
        return $this->belongsTo(Account::class, 'tpa_account_id')->with('account_contact', 'account_title', 'company');
    }
    public function getIpdBed()
    {
        return $this->hasOne(Bed::class, 'ipd_admission_no', 'id');
    }
    public function beds()
    {
        return $this->hasMany(Bed::class, 'ipd_admission_no');
    }

    public function bedHistory()
    {
        return $this->hasMany(IpdBedHistory::class, 'ipd_admission_id', 'id')->whereNotNull('check_out_date_time');
    }

    static public function getStatement($where = [], $filter = []) {

        $data = IpdAdmission::select([
                'ipd_admissions.id',
                'ipd_admissions.ipd_admission_no',
                'ipd_admissions.last_ipd_no_count',
                'ipd_admissions.ipd_file_no',
                'ipd_admissions.claim_number',
                'ipd_admissions.admission_date_time',
                'ipd_admissions.patient_id as patient_id',
                'ipd_admissions.patient_source_id',
                'ipd_admissions.tpa_account_id',
                'ipd_admissions.casualty',
                'ipd_admissions.referal_from',
                'ipd_admissions.note',
                'ipd_admissions.referral_partner_id',
                'ipd_admissions.previous_medical_issue',
                'ipd_admissions.sale_invoice_id',
                'ipd_admissions.deleted_at',
                'ipd_admissions.created_at',
                'ipd_admissions.updated_at',
                'ipd_admissions.created_by' , 
                'accounts.company_id as company_id',
                'products.category_id' ,    
                'accounts.name as account_name',

        ])
        ->leftJoin('ipd_admission_discharged', 'ipd_admission_discharged.discharge_type_id', '=', 'ipd_admissions.id')
        ->leftJoin('ipd_discharges' , 'ipd_discharges.ipd_admission_id', '=', 'ipd_admissions.id')
        ->leftJoin('accounts' , 'accounts.id', '=', 'ipd_admissions.patient_id')
        ->leftJoin('companies' ,  'companies.id', '=', 'accounts.company_id')
        ->leftJoin('sale_invoice_details' , 'sale_invoice_details.sale_invoice_id' , '=' , 'ipd_admissions.sale_invoice_id')
        ->leftJoin('products' , 'sale_invoice_details.product_id' , '=' , 'products.id')
        ->leftJoin('product_categories' , 'product_categories.id' , '=' , 'products.category_id')
        ->when(!empty($where), function($query) use ($where) {
        $query->where($where);
        })
       ->when(!empty($filter['from_date']) && !empty($filter['to_date']), function($query) use ($filter) {
            $query->when($filter['from_date'] == $filter['to_date'], function($query) use ($filter) {
                // $query->whereDate('ipd_admissions.admission_date_time', '=', $filter['from_date']);
            })
            ->when($filter['from_date'] != $filter['to_date'], function($query) use ($filter) {
                $query->whereBetween('ipd_admissions.admission_date_time', [$filter['from_date'], $filter['to_date']]);
            });
        })
        ->when(!empty($filter['search_account']), function($query) use ($filter) {
            $query->where(function($query) use ($filter) {
                $query->where('accounts.name', 'LIKE', '%' . $filter['search_account'] . '%');
            });
        })
        ->when(!empty($filter['category']), function($query) use ($filter) {
            $query->where('products.category_id', $filter['category']);
        })
        ->when(!empty($filter['company']), function($query) use ($filter) {
            $query->where('accounts.company_id', $filter['company']);
        })
        ->when(!empty($filter['created_by']), function($query) use ($filter) {
            $query->where('ipd_admissions.created_by', $filter['created_by']);
        })
        ->when(!empty($filter['listing_type']), function($query) use ($filter){
            if ($filter['listing_type'] == 'active') {
            $query->whereNull('ipd_discharges.id');
        } elseif ($filter['listing_type'] == 'discharged') {
            $query->whereNotNull('ipd_discharges.id');
        }
        })
        ->groupBy('ipd_admissions.id')
        ->get();

        return $data;
    }


   
}

