@extends('emails.standard.master')

@section('title')
	Unknown NR Flow Device Login
@endsection

@section('headline1', 'An unknown device has been recorded logging into your account')

@section('button-text', 'Change Password')

@section('button-link', 'https://wallet.nygmarosebeauty.com/settings')

@section('headline2', 'Unknown Device')

@section('bold-text')
	<p>Device: {{ $agent['device'] }} {{ $agent['platform'] }} using {{ $agent['browser'] }}</p>
	<p>IP Address: {{ $agent['ip'] }}</p>
@endsection

@section('text')
	If this device was in fact you then there's no need to take any action for this.
@endsection
