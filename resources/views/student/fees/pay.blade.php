@extends('student.layouts.master')
@section('title', $title)
@section('content')

<!-- Start Content-->
<div class="main-body">
    <div class="page-wrapper">
        <!-- [ Main Content ] start -->
        <div class="row">
            <div class="col-sm-8">
                <div class="card">
                    <div class="card-header">
                        <h5>{{ $title }}</h5>
                    </div>
                    <div class="card-block">
                        <a href="{{ route($route.'.index') }}" class="btn btn-primary"><i class="fas fa-arrow-left"></i> {{ __('btn_back') }}</a>
                    </div>
                    <form method="post" action="{{ route('payment.stripe.process') }}" id="stripe-form">
                    @csrf
                    <div class="card-block">
                    <!-- Details View Start -->
                    <div class="">
                        <div class="row">
                            <div class="col-md-6">
                                <p><mark class="text-primary">{{ __('field_student_id') }}:</mark> #{{ $row->studentEnroll->student->student_id ?? '' }}</p><hr/>
                                <p><mark class="text-primary">{{ __('field_program') }}:</mark> {{ $row->studentEnroll->program->title ?? '' }}</p><hr/>
                                <p><mark class="text-primary">{{ __('field_fees_type') }}:</mark> {{ $row->category->title ?? '' }}</p><hr/>
                            </div>
                            <div class="col-md-6">
                                <p><mark class="text-primary">{{ __('field_session') }}:</mark> {{ $row->studentEnroll->session->title ?? '' }}</p><hr/>
                                <p><mark class="text-primary">{{ __('field_semester') }}:</mark> {{ $row->studentEnroll->semester->title ?? '' }}</p><hr/>
                                <p><mark class="text-primary">{{ __('field_section') }}:</mark> {{ $row->studentEnroll->section->title ?? '' }}</p><hr/>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="form-group col-md-6">
                                <label for="due_date" class="form-label">{{ __('field_due_date') }} <span>*</span></label>
                                <input type="date" class="form-control" name="due_date" id="due_date" value="{{ $row->due_date }}" required readonly>

                                <div class="invalid-feedback">
                                  {{ __('required_field') }} {{ __('field_due_date') }}
                                </div>
                            </div>

                            <div class="form-group col-md-6">
                                <label for="pay_date" class="form-label">{{ __('field_pay_date') }} <span>*</span></label>
                                <input type="date" class="form-control" name="pay_date" id="pay_date" value="{{ date('Y-m-d') }}" required readonly>

                                <div class="invalid-feedback">
                                  {{ __('required_field') }} {{ __('field_pay_date') }}
                                </div>
                            </div>

                            <div class="form-group col-md-6">
                                <label for="fee_amount" class="form-label">{{ __('field_amount') }} ({!! $setting->currency_symbol !!}) <span>*</span></label>
                                <input type="text" class="form-control autonumber" name="fee_amount" id="fee_amount" value="{{ round($row->fee_amount, 2) }}" required readonly>

                                <div class="invalid-feedback">
                                  {{ __('required_field') }} {{ __('field_amount') }}
                                </div>
                            </div>

                            @php 
                            $discount_amount = 0;
                            $today = date('Y-m-d');
                            @endphp

                            @isset($row->category)
                            @foreach($row->category->discounts->where('status', '1') as $discount)

                            @php
                            $availability = \App\Models\FeesDiscount::availability($discount->id, $row->studentEnroll->student_id);
                            @endphp

                            @if(isset($availability))
                            @if($discount->start_date <= $today && $discount->end_date >= $today)
                                @if($discount->type == '1')
                                    @php
                                    $discount_amount = $discount_amount + $discount->amount;
                                    @endphp
                                @else
                                    @php
                                    $discount_amount = $discount_amount + ( ($row->fee_amount / 100) * $discount->amount);
                                    @endphp
                                @endif
                            @endif
                            @endif
                            @endforeach
                            @endisset

                            <div class="form-group col-md-6">
                                <label for="discount_amount" class="form-label">{{ __('field_discount') }} ({!! $setting->currency_symbol !!}) <span>*</span></label>
                                <input type="text" class="form-control autonumber" name="discount_amount" id="discount_amount" value="{{ round($discount_amount, 2) }}" required readonly>

                                <div class="invalid-feedback">
                                  {{ __('required_field') }} {{ __('field_discount') }}
                                </div>
                            </div>

                            @php
                            $fine_amount = 0;
                            @endphp
                            @if(empty($row->pay_date) || $row->due_date < $row->pay_date)
                                
                                @php
                                $due_date = strtotime($row->due_date);
                                $today = strtotime(date('Y-m-d')); 
                                $days = (int)(($today - $due_date)/86400);
                                @endphp

                                @if($row->due_date < date("Y-m-d"))
                                @isset($row->category)
                                @foreach($row->category->fines->where('status', '1') as $fine)
                                @if($fine->start_day <= $days && $fine->end_day >= $days)
                                    @if($fine->type == '1')
                                        @php
                                        $fine_amount = $fine_amount + $fine->amount;
                                        @endphp
                                    @else
                                        @php
                                        $fine_amount = $fine_amount + ( ($row->fee_amount / 100) * $fine->amount);
                                        @endphp
                                    @endif
                                @endif
                                @endforeach
                                @endisset
                                @endif
                            @endif

                            <div class="form-group col-md-6">
                                <label for="fine_amount" class="form-label">{{ __('field_fine_amount') }} ({!! $setting->currency_symbol !!}) <span>*</span></label>
                                <input type="text" class="form-control autonumber" name="fine_amount" id="fine_amount" value="{{ round($fine_amount, 2) }}" required readonly>

                                <div class="invalid-feedback">
                                  {{ __('required_field') }} {{ __('field_fine_amount') }}
                                </div>
                            </div>

                            @php
                            $net_amount = ($row->fee_amount - $discount_amount) + $fine_amount;
                            @endphp

                            <div class="form-group col-md-6">
                                <label for="net_amount" class="form-label">{{ __('field_net_amount') }} ({!! $setting->currency_symbol !!}) <span>*</span></label>
                                <input type="text" class="form-control autonumber" name="paid_amount" id="net_amount" value="{{ round($net_amount, 2) }}"  required readonly>

                                <div class="invalid-feedback">
                                  {{ __('required_field') }} {{ __('field_net_amount') }}
                                </div>
                            </div>
                        </div>

                        @if(config('payment.status') == 'stripe')
                        <div class="row">
                            <div class="col-md-12 mt-2 text-center">
                                <img src="{{ asset('dashboard/images/stripe.png') }}" class="img-fluid">
                            </div>
                            <div class="col-md-12 mt-2">
                                
                                <input type='hidden' name='stripeToken' id='stripe-token'>
                                <input type="hidden" name="fee_id" value="{{ $row->id }}">

                                <div id="stripe-card-element" class="form-control"></div>
                                
                            </div>
                        </div>
                        @endif
                    </div>
                    <!-- Details View End -->
                    </div>
                    <div class="card-footer">
                        <button type="button" id="stripe-pay-btn" class="btn btn-primary" onclick="createToken()"><i class="fas fa-money-check"></i> {{ __('btn_pay') }}</button>
                    </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>
<!-- End Content-->

@endsection

@section('page_js')
@if(config('payment.status') == 'stripe')
<script src="https://js.stripe.com/v3/"></script>
<script type="text/javascript">

    var stripe = Stripe('{{ env('STRIPE_KEY') }}');
    var elements = stripe.elements();
    var cardElement = elements.create('card', {
        hidePostalCode: true,
        style: {
         base: {
          color: '#495057',
          lineHeight: '30px',
          fontWeight: 300,
          fontSize: '16px',

          '::placeholder': {
            color: '#888',
           },
          }, 
        }
    });
    cardElement.mount('#stripe-card-element');

    /*------------------------------------------
    --------------------------------------------
    Create Token Code
    --------------------------------------------
    --------------------------------------------*/
    function createToken() {

        document.getElementById("stripe-pay-btn").disabled = true;

        stripe.createToken(cardElement).then(function(result) {
            if(typeof result.error != 'undefined') {
                document.getElementById("stripe-pay-btn").disabled = false;
                alert(result.error.message);
            }

            /* creating token success */
            if(typeof result.token != 'undefined') {
                document.getElementById("stripe-token").value = result.token.id;
                document.getElementById('stripe-form').submit();
            }
        });
    }
</script>
@endif
@endsection