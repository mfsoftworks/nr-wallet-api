@extends('emails.standard.master')

@section('title')
	New NR Flow Login
@endsection

@section('headline1', 'A device has been recorded logging into your account')

@section('button-text', 'Open App')

@section('button-link', 'https://wallet.nygmarosebeauty.com/')

@section('headline2', 'Login Device')

@section('bold-text')
	<p>Device: {{ $agent['device'] }} {{ $agent['platform'] }} using {{ $agent['browser'] }}</p>
	<p>IP Address: {{ $agent['ip'] }}</p>
@endsection

@section('text')
	If this device was in fact you then there's no need to take any action for this.
@endsection
