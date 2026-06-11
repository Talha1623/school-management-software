@extends('layouts.accountant')

@section('title', 'Generate Transport Fee')

@section('content')
    @include('accounting.partials.generate-transport-fee-form', [
        'transportFeeFormRoute' => 'accountant.generate-transport-fee.store',
        'transportFeeClassesRoute' => 'accountant.transport-fee.get-classes-by-campus',
        'transportFeeSectionsRoute' => 'accountant.transport-fee.get-sections-by-class',
        'transportFeeStudentsRoute' => 'accountant.transport-fee.get-students-with-fee-status',
    ])
@endsection
