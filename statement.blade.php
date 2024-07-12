@extends('layouts.app')
@section('title','IPD Statement')
@section('pages')
<style type="text/css">
  .breadcrumb-title {
    border-right: none;
  }
  .thbutton {
    width: 7% !important;
}
</style>
@php $pageTitle="IPD Statement"; @endphp

<link rel="stylesheet" type="text/css" href="{{ asset('admin/plugins/datepicker/less/datepicker.less') }}">
<link href="{{ asset('admin/plugins/datepicker/css/datepicker.css') }}" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/12.1.10/css/intlTelInput.css">
<link href="{{ asset('admin/css/print.css')}}" rel="stylesheet" />

<!--breadcrumb-->
<div class="page-breadcrumb d-flex align-items-center mb-3 px-3 px-md-0">
  <div class="breadcrumb-title pe-3"><i class="bi bi-list"></i> IPD Statement</div>

  <div class="ms-auto">
    <div class="btn-group">
      <a href="{{route('ipd-admissions.index')}}" class="btn btn-outline-secondary me-2"><i class="bx bx-arrow-back ms-0 me-1"></i>Back</a>
    </div>
  </div>
</div>
<!--end breadcrumb-->

<div class="card shadow-none radius-5 min-height">
  <div class="card-header py-3">
    @include('layouts.partials.nav-menu')
    <div class="row gx-2 custom_grid align-items-end gx-0 mt-4 mb-3">

      <div class="col-lg-3 col-md-3 col-3 mb-3">
        <div class="input-group">
          <label class="form-label">Company <span class="text-danger"><b></b></span></label>
          <select class="form-control br-0" id="company" name="company" required>
            <option value="">All Companies</option>
            @foreach($companies as $list)
              <option value="{{ $list->id}}">{{ $list->name }}</option>
            @endforeach
          </select>
        </div>
      </div>

      <div class="col-lg-3 col-md-3 col-3 mb-3">
        <div class="input-group">
          <label class="form-label">From Date <span class="text-danger"><b></b></span></label>
          <input class="form-control" type="text" id="from_date" name="from_date" placeholder="dd/mm/YY" value="">
        </div>

      </div>
      <div class="col-lg-3 col-md-3 col-3 mb-3">
        <div class="input-group">
          <label class="form-label">To Date <span class="text-danger"><b></b></span></label>
          <input class="form-control" type="text" id="to_date" name="to_date" placeholder="dd/mm/YY" value="">

        </div>
      </div>

      <div class="col-lg-3 col-md-3 col-3 mb-3">
        <div class="input-group">
          <label class="form-label"> Listing Type <span class="text-danger"><b></b></span></label>
          <select class="form-control br-0" id="listing_type" name="listing_type" required>
              <option value="">All Patient</option>
              <option value="active">Active In Patient</option>
              <option value="discharged">Discharged Patient</option>
          </select>
            <a href="javascript:void(0)" class="hiddenReferral fw-bold" id="add_reference" style="padding: 10px;"><i class="bx bx-plus"></i></a>
        </div>
      </div>

      <div class="col-lg-3 col-md-3 col-3">
        <div class="input-group references" style="display:none;">
          <label class="form-label"> Category <span class="text-danger"><b></b></span></label>
          <select class="form-control br-0" id="category" name="category" required>
            <option value="">All Category</option>
            @foreach($productCategory as $list)
              <option value="{{ $list->id}}">{{ $list->name }}</option>
            @endforeach
           
          </select>
        </div>
      </div>

      

      <div class="col-lg-2 col-md-2 col-2">
        <div class="input-group references" style="display:none;">
          <label class="form-label">User Name / ID<span class="text-danger"><b></b></span></label>
          <select name="created_by" class="form-control" id="created_by">
            @if(in_array('Admin',auth::user()->roles->pluck('name')->all()))
            <option value="" selected>All User</option>
            @endif
            @foreach($users as $key => $value)
              @if(in_array('Admin',auth::user()->roles->pluck('name')->all()))
              <option value="{!! $value->id !!}" {!! $value->id==auth::user()->id && auth::user()->user_designation!='Admin'?'selected':''  !!}>{!! $value->name !!} ({!! $value->email !!})</option>
              @elseif($value->id==auth::user()->id)
              <option value="{!! $value->id !!}" {!! $value->id==auth::user()->id && auth::user()->user_designation!='Admin'?'selected':''  !!}>{!! $value->name !!} ({!! $value->email !!})</option>
              @endif
            @endforeach
          </select>
        </div>
      </div>

      <div class="col-lg-3 col-md-3 col-3">
        <div class="input-group references" style="display:none;">
          <label class="form-label">Search Account <span class="text-danger"><b></b></span></label>
          <input type="text" name="search_account" id="search_account" class="form-control" placeholder="Search Account">
        </div>
      </div>

    </div>
    <div class="row mt-2">
      <div class="col-lg-12 col-md-12 col-12 text-center">
        <button class="btn btn-primary" id="submit" type="button" style="height: 40px; width: 25% !important;"><i class="bi bi-search"></i> Search</button>
      </div>
    </div>
    <input type="hidden" name="hidden_page" id="hidden_page" value="1" />
    <input type="hidden" name="hidden_column_name" id="hidden_column_name" value="name" />
    <input type="hidden" name="hidden_sort_type" id="hidden_sort_type" value="asc" />
  <hr>
    <div class="card-body" >
        <div class="row" style="">
          <div class="col-md-12" id="tables">
            
          </div>
        </div>
      
    </div>
    <script type="text/javascript" src="{{ asset('admin/plugins/datepicker/js/bootstrap-datepicker.js')}}"></script>
    <script>

      $(document).on('click', '#add_reference', function() {
        if ($('#add_reference').hasClass('hiddenReferral')) {
            $('.references').show();
            $('#add_reference').html('<i class="bx bx-minus"></i>');
            $('#add_reference').removeClass('hiddenReferral');
        } else {
            $('.references').hide();
            $('#add_reference').html('<i class="bx bx-plus"></i>');
            $('#add_reference').addClass('hiddenReferral');
        }
      });
       $('[name="from_date"]').datepicker({
        format: "{!! $companyDateFormate??'dd-mm-yyyy' !!}",
        autoclose: true,
        //startDate: financialYearDates.fromDate,//optional
        //endDate: financialYearDates.toDate//optional
        }).datepicker("setDate", new Date());
      $('[name="to_date"]').datepicker({
      format: "{!! $companyDateFormate??'dd-mm-yyyy' !!}",
      autoclose: true,
      //startDate: financialYearDates.fromDate,//optional
      //endDate: financialYearDates.toDate//optional
      }).datepicker("setDate", new Date());
      $(document).ready(function() {



        function clear_icon() {
          $('#id_icon').html('');
          $('#post_title_icon').html('');
        }

        function fetch_data(category, from_date, to_date, search_account, collection_status, company, created_by, listing_type) {
          $.ajax({
              url: "?category=" + category + "&from_date=" + from_date + "&listing_type=" + listing_type + "&to_date=" + to_date + "&search_account=" + search_account + "&collection_status=" + collection_status + "&company=" + company + "&created_by=" + created_by,
              success: function(data) {
                  $('#tables').html('');
                  $('#tables').html(data);
                  $(document).ready(function() {
                      $.switcher();
                  });
                  $('#example').dataTable({
                      paging: false,
                      searching: false,
                      info: false
                  });
              }
          });
        }



        $("body").on("click", '#submit', function(event) {
          var category = $('#category').val();
          var listing_type = $('#listing_type').val(); 
          var from_date = $('#from_date').val();
          var to_date = $('#to_date').val();
          var search_account = $('#search_account').val();
          var collection_status = $('#collection_status').val();
          var created_by = $('#created_by').val();
          var company = $('#company').val();

          fetch_data(category, from_date, to_date, search_account, collection_status, company, created_by, listing_type); 
        });


        $("body").on("click", '.export-statement', function(event) {
         var category = $('#category').val();
          var listing_type = $('#listing_type').val(); 
          var from_date = $('#from_date').val();
          var to_date = $('#to_date').val();
          var search_account = $('#search_account').val();
          var collection_status = $('#collection_status').val();
          var created_by = $('#created_by').val();
          var company = $('#company').val();
          var url          = "?category=" + category + "&from_date=" + from_date + "&listing_type=" + listing_type + "&to_date=" + to_date + "&search_account=" + search_account + "&collection_status=" + collection_status + "&company=" + company + "&created_by=" + created_by;
          window.location.href="{!! route('ipd-billing-reports-export') !!}?" + url; 
    });


      });
    </script>



    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.3.4/jspdf.min.js"></script>

    <script>
    $(document).ready(function() {
        var page_title = $('#page_title').val();
        $("title").html(page_title);
        $(document).on('click', '#btn_print', function(event) {
            event.preventDefault();
            $('#btn_back').hide();
            printInvoice();
            return;
            // printInvoice(page_title);
            /*$('#print_div').printThis({
                importStyle: true,
            });*/
            $(this).hide();
            var printContents = document.getElementById('print_div').innerHTML;
            var originalContents = document.body.innerHTML;

            document.body.innerHTML = printContents;

            window.print();

            document.body.innerHTML = originalContents;

            $(this).show();
            $('#btn_back').show();
            $('#btn_print').show();
        });

        $(document).on('click', '#btn_pdf', function(e) {
            html2canvas($('#print_div'), {
                background: "#ffffff",
                onrendered: function(canvas) {
                    var myImage = canvas.toDataURL("image/png", 1.0);
                    // Adjust width and height
                    var imgWidth = (canvas.width * 43) / 250;
                    var imgHeight = (canvas.height * 48) / 250;
                    // jspdf changes
                    var pdf = new jsPDF('p', 'mm', 'a4');
                    pdf.addImage(myImage, 'png', 5, 5, imgWidth, imgHeight); // 2: 19
                    pdf.save(`${$('#page_title').val()}.pdf`);
                }
            });
        });
    });

    function printInvoice() {
        let printDiv = $('#print_div').html();
        let content = window.open('', '', 'height=750px,width=960px');
        let doc = content.document;
        let head = doc.head;
        let body = doc.body;

        $(head).append('<Title>{{$pageTitle}}</Title>');
        $(head).append('<link rel="stylesheet" href="{{ asset('admin/css/bootstrap.min.css') }}" type="text/css">');
        $(head).append('<link rel="stylesheet" href="{{ asset('admin/css/print.css') }}" type="text/css">');
        $(head).append('<style>@page {size: auto;}</style>');
        $(body).append(printDiv);

        setTimeout(function() {
            content.print();
        }, 500);
    }
</script>
    @endsection