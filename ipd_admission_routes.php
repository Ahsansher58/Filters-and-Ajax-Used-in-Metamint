<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IpdAdmission\BedController;
use App\Http\Controllers\IpdAdmission\BedGroupController;
use App\Http\Controllers\IpdAdmission\BedTypeController;
use App\Http\Controllers\IpdAdmission\BuildingController;
use App\Http\Controllers\IpdAdmission\FloorController;
use App\Http\Controllers\IpdAdmission\IpdDischargedController;
use App\Http\Controllers\IpdAdmission\IpdBillingsController;
use App\Http\Controllers\IpdAdmission\IpdAdmissionsController;
use App\Http\Controllers\IpdAdmission\IpdDischargeTypeSettingsController;
use App\Http\Controllers\RackLocationController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\TreatmentTypeController;


   /********************************** Ipd-Admissions ************************************/ 

  Route::any('/get-patients-list', [IpdAdmissionsController::class, 'getPatientsList'])->name('ipd-admissions.getPatientsList');
  Route::resource('ipd-admissions', IpdAdmissionsController::class);
  Route::any('ipd-billing-reports', [IpdAdmissionsController::class, 'statement'])->name('ipd-billing-reports'); 
  Route::any('ipd-billing-reports-export', [IpdAdmissionsController::class, 'exportStatement'])->name('ipd-billing-reports-export');
  

  Route::prefix('/ipd-admissions/')->group(function () {
      Route::get('/create/{id?}', [IpdAdmissionsController::class, 'create'])->name('ipd-admissions.create');
      Route::any('/delete', [IpdAdmissionsController::class, 'destroy'])->name('ipd-admissions.destroy');
      Route::any('/change-status', [IpdAdmissionsController::class, 'changeStatus'])->name('ipd-admissions.changestatus');

     
      
     

      // SYMPTOMS
      Route::any('/symptoms/{id}', [IpdAdmissionsController::class, 'symptom'])->name('ipd-admission-symptoms.index');
      Route::any('/symptoms-create/{id}', [IpdAdmissionsController::class, 'createSymptom'])->name('ipd-admission-symptoms.create');
      Route::any('/symptoms-store/{id}', [IpdAdmissionsController::class, 'StoreSymptom'])->name('ipd-admission-symptoms.store');
      Route::any('/symptoms-destroy', [IpdAdmissionsController::class, 'destroySymptom'])->name('ipd-admission-symptoms.destroy');
      Route::any('/get/symptoms-title', [IpdAdmissionsController::class, 'getSymptomsTitle'])->name('ipd-admissions.get-symptoms-title');
      Route::any('/get/symptoms-description', [IpdAdmissionsController::class, 'getSymptomsDescription'])->name('ipd-admissions.get-symptoms-description');
      Route::any('/get/beds', [IpdAdmissionsController::class, 'getBeds'])->name('ipd-admissions.get-beds');

      // CONSULTANTS
      Route::any('/consultants/{id}', [IpdAdmissionsController::class, 'Consultant'])->name('ipd-admission-consultants.index');
      Route::any('/create-consultant/{id}', [IpdAdmissionsController::class, 'createConsultant'])->name('ipd-admission-consultants.create');
      Route::any('/store-consultant/{id}', [IpdAdmissionsController::class, 'storeConsultant'])->name('ipd-admission-consultants.store');
      Route::any('/destroy-consultant', [IpdAdmissionsController::class, 'destroyConsultant'])->name('ipd-admission-consultants.destroy');

      // BEDS
      Route::any('/beds/{id}', [IpdAdmissionsController::class, 'Bed'])->name('ipd-admission-beds.index');  
      Route::any('/create-bed/{id}', [IpdAdmissionsController::class, 'createBed'])->name('ipd-admissions-beds.create');
      Route::any('/create-step-2/{id}', [IpdAdmissionsController::class, 'createBedStep2'])->name('ipd-admissions-beds.create-step-2');
      Route::any('/store-bed/{id}', [IpdAdmissionsController::class, 'storeBed'])->name('ipd-admissions-beds.store');
      Route::any('/edit-bed/{id}', [IpdAdmissionsController::class, 'editBed'])->name('ipd-admissions-beds.edit');
      Route::any('/update-bed/{id}', [IpdAdmissionsController::class, 'updateBed'])->name('ipd-admissions-beds.update');
      Route::any('/destroy-bed', [IpdAdmissionsController::class, 'destroyBed'])->name('ipd-admissions-beds.destroy');
      Route::any('/beds/allotment-status/{id}', [IpdAdmissionsController::class, 'bedsAllotmentStatus'])->name('ipd-admissions.bed-allotment.status');

      // BODY VITALS
      Route::any('/body-vitals/{id}', [IpdAdmissionsController::class, 'bodyVitals'])->name('ipd-admissions-body-vitals.index');
      Route::any('/body-vitals-create/{id}', [IpdAdmissionsController::class, 'createBodyVitals'])->name('ipd-admissions-body-vitals.create');
      Route::any('/body-vitals-store/{id}', [IpdAdmissionsController::class, 'storeBodyVitals'])->name('ipd-admissions-body-vitals.store');
      Route::any('/body-vitals-edit/{id}', [IpdAdmissionsController::class, 'editBodyVitals'])->name('ipd-admissions-body-vitals.edit');
      Route::any('/body-vitals-update/{id}', [IpdAdmissionsController::class, 'updateBodyVitals'])->name('ipd-admissions-body-vitals.update');
      Route::any('/body-vitals-destroy', [IpdAdmissionsController::class, 'destroyBodyVitals'])->name('ipd-admissions-body-vitals.destroy');

      // IPD-ADMISSION DISCHARGED
      Route::any('/patient-discharge-edit/{id}', [IpdAdmissionsController::class, 'patientDischargeEdit'])->name('ipd-admissions.patientdischargeedit');
      Route::any('/patient-discharge-update/{id}', [IpdAdmissionsController::class, 'patientDischargeUpdate'])->name('ipd-admissions.patientdischargeupdate');
      Route::any('/verify-checklist/{id}', [IpdAdmissionsController::class, 'verifyChecklist'])->name('ipd-billings.verify-checklist');
      Route::any('/discharge-statement/{id}', [IpdAdmissionsController::class, 'dischargeStatement'])->name('ipd-admissions.dischargestatement');
     

   });
   /**************************  Bed Group  *******************************/
   Route::resource('bed-group', BedGroupController::class);
   Route::group(['prefix' => 'bed-group'], function () {
      Route::any('/delete', [BedGroupController::class, 'destroy'])->name('bed-group.destroy');
      Route::any('/changeStatus', [BedGroupController::class, 'changeStatus'])->name('bed-group.changestatus');
   });
   Route::post('/getFloor', [BedGroupController::class, 'getFloor'])->name('getFloor');
   /**************************  Floor  *******************************/
   Route::resource('floors', FloorController::class);
   Route::group(['prefix' => 'floors'], function () {
      Route::any('/delete', [FloorController::class, 'destroy'])->name('floors.destroy');
      Route::any('/changeStatus', [FloorController::class, 'changeStatus'])->name('floors.changestatus');
      Route::any('/changedefault', [FloorController::class, 'changedefault'])->name('floors.changedefault');
   });
   /**************************  Bed Type  *******************************/
   Route::resource('bed-type', BedTypeController::class);
   Route::group(['prefix' => 'bed-type'], function () {
      Route::any('/delete', [BedTypeController::class, 'destroy'])->name('bed-type.destroy');
      Route::any('/changeStatus', [BedTypeController::class, 'changeStatus'])->name('bed-type.changestatus');
      Route::any('/changedefault', [BedTypeController::class, 'changedefault'])->name('bed-type.changedefault');
      Route::any('/changedefault', [BedTypeController::class, 'changedefault'])->name('bed-type.changedefault');
      Route::post('/bed-type-search-product', [BedTypeController::class, 'bedTypeSearchProducts'])->name('bed-type-search-products');
   });
   /**************************  Beds  *******************************/
   Route::resource('beds', BedController::class);
   Route::group(['prefix' => 'beds'], function () {
      Route::any('/delete', [BedController::class, 'destroy'])->name('beds.destroy');
      Route::any('/changeStatus', [BedController::class, 'changeStatus'])->name('beds.changestatus');
      Route::any('/waitingPatient', [BedController::class, 'waitingPatient'])->name('waiting-patient.status');
      Route::any('/filter/beds', [BedController::class, 'filterBeds'])->name('beds.filterbeds');
   });
   Route::any('/beds-import-view', [BedController::class, 'viewImport'])->name('beds-import.view');
   Route::any('/beds-import', [BedController::class, 'bedImport'])->name('beds.import');
   Route::any('/bed-status', [BedController::class, 'bedstatus'])->name('bed.status');
   Route::any('/beds-available', [BedController::class, 'bedsavailable'])->name('beds.available');


   /**************************  buildings  *******************************/
   Route::resource('buildings', BuildingController::class);
   Route::group(['prefix' => 'buildings'], function () {
      Route::get('/list', [BuildingController::class, 'list']);
      Route::any('/delete', [BuildingController::class, 'destroy'])->name('buildings.destroy');
      Route::any('/changestatus', [BuildingController::class, 'changeStatus'])->name('buildings.changestatus');
      Route::any('/changedefault', [BuildingController::class, 'changedefault'])->name('buildings.changedefault');
    });

    /********************** IPD Discharged ****************************/
    Route::resource('ipd-discharged', IpdDischargedController::class);
    Route::any('/dischargeInvoice/{id}', [IpdDischargedController::class, 'dischargeInvoice'])->name('ipd-discharged.invoice');
    Route::any('ipd-discharged-reports', [IpdDischargedController::class, 'statement'])->name('ipd-discharged-reports'); 
    Route::any('ipd-discharged-reports-export', [IpdDischargedController::class, 'exportStatement'])->name('ipd-discharged-reports-export');


   /********************** IPD Billings ****************************/
   Route::prefix('/ipd-billings/')->group(function () {
      Route::get('/{id?}', [IpdBillingsController::class, 'index'])->name('ipd-billings.index');
      Route::get('/show/{id}', [IpdBillingsController::class, 'show'])->name('ipd-billings.show');
      Route::get('/edit/{id}/{batch_id}', [IpdBillingsController::class, 'edit'])->name('ipd-billings.edit');
      Route::any('/update/{id}/{batch_id}', [IpdBillingsController::class, 'update'])->name('ipd-billings.update');
      Route::get('/create/{id}', [IpdBillingsController::class, 'create'])->name('ipd-billings.create');
      Route::get('/AddMore/{id}', [IpdBillingsController::class, 'AddMore'])->name('ipd-billings.add-more');
      Route::post('/store', [IpdBillingsController::class, 'store'])->name('ipd-billings.store');
      Route::post('/search-product', [IpdBillingsController::class, 'searchProducts'])->name('ipd-billings-search-product');
      Route::any('/product-cart/{invoice_id?}/{batch_id?}', [IpdBillingsController::class, 'productCart'])->name('ipd-billing-product-cart');
      Route::any('/products-add-to-cart', [IpdBillingsController::class, 'productAddToCart'])->name('ipd-products-add-to-cart');
      Route::any('/cart-product-remove', [IpdBillingsController::class, 'cartProductRemove'])->name('ipd-cart-product-remove');
      Route::any('/cart-product-update', [IpdBillingsController::class, 'cartProductUpdate'])->name('ipd-cart-product-update');
      Route::get('/cart-billing/{id}', [IpdBillingsController::class, 'billing'])->name('ipd-cart-billing');
      Route::get('/edit-cart-billing/{invoice_id}/{batch_id}', [IpdBillingsController::class, 'editBilling'])->name('ipd-edit-cart-billing');
      Route::get('/invoice/{id}', [IpdBillingsController::class, 'ipdInvoice'])->name('ipd-invoice');
      Route::get('/invoice/{id}/{batch_id}',[IpdBillingsController::class, 'ipdNewInvoice'])->name('ipd-new-invoice');
      Route::get('/report/{invoice_id}', [IpdBillingsController::class, 'billingReport'])->name('ipd-billing-report');
      Route::post('/report/{invoice_id}', [IpdBillingsController::class, 'billingReportStore'])->name('ipd-billing-report.store');
      Route::post('/report-departments', [IpdBillingsController::class, 'billingReportDepartment'])->name('ipd-billing-report-departments');
      Route::get('/print-report/{invoice_id}', [IpdBillingsController::class, 'billingReportPrint'])->name('ipd-print-billing-report');
      Route::get('/report-logs/{invoice_id}', [IpdBillingsController::class, 'billingReportLog'])->name('ipd-billing-report-logs');
      Route::get('/summary/{patient_id}', [IpdBillingsController::class, 'billingSummary'])->name('ipd-billings-summary');
   });
    /**************************  Rooms  *******************************/
    Route::resource('rooms', RoomController::class);
    Route::group(['prefix' => 'rooms'], function () {
       Route::get('/list', [RoomController::class, 'list']);
       Route::any('/delete', [RoomController::class, 'destroy'])->name('rooms.destroy');
       Route::any('/changestatus', [RoomController::class, 'changeStatus'])->name('rooms.changestatus');
       Route::any('/changedefault', [RoomController::class, 'changedefault'])->name('rooms.changedefault');
    });
    /**************************  Rack Location  *******************************/
    Route::resource('rack-locations', RackLocationController::class);
    Route::group(['prefix' => 'rack-locations'], function () {
       Route::get('/list', [RackLocationController::class, 'list']);
       Route::any('/delete', [RackLocationController::class, 'destroy'])->name('rack-locations.destroy');
       Route::any('/changestatus', [RackLocationController::class, 'changeStatus'])->name('rack-locations.changestatus');
       Route::any('/changedefault', [RackLocationController::class, 'changedefault'])->name('rack-locations.changedefault');
    });

   /********************************** Master Settings ***********************************/
    Route::resource('ipd-discharge-type-settings', IpdDischargeTypeSettingsController::class);
    Route::any('ipd-discharge-type-settings/changestatus', [IpdDischargeTypeSettingsController::class, 'changestatus'])->name('ipd-discharge-type-settings.changestatus');
    Route::any('ipd-discharge-type-settings/delete', [IpdDischargeTypeSettingsController::class, 'destroy'])->name('ipd-discharge-type-settings.destroy');
    Route::get('ipd-discharge-type-settings/list/{id?}', [IpdDischargeTypeSettingsController::class , 'index'])->name('ipd-discharge-type-settings-list');


  /********************************** Treatment Types ***********************************/
    Route::resource('treatment-types', TreatmentTypeController::class);
     Route::any('treatment-types/delete', [TreatmentTypeController::class, 'destroy'])->name('treatment-types.destroy');
     Route::any('treatment-types/changestatus', [TreatmentTypeController::class, 'changestatus'])->name('treatment-types.changestatus');
      Route::any('treatment-types/changedefault', [TreatmentTypeController::class, 'changedefault'])->name('treatment-types.changedefault');



        