@extends('layouts.app')

@section('title', 'Generate Transport Fee')

@section('content')
    @include('accounting.partials.generate-transport-fee-form', [
        'transportFeeFormRoute' => 'accounting.generate-transport-fee.store',
        'transportFeeClassesRoute' => 'accounting.transport-fee.get-classes-by-campus',
        'transportFeeSectionsRoute' => 'accounting.transport-fee.get-sections-by-class',
        'transportFeeStudentsRoute' => 'accounting.transport-fee.get-students-with-fee-status',
    ])
@endsection
