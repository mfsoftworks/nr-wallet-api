@extends('emails.standard.master')

@section('title')
	WELCOME {{ $username }}
@endsection

@section('headline1', 'Welcome to the new NR Flow comprehensive wallet')

@section('button-text', 'Let it Flow')

@section('button-link', 'https://wallet.nygmarosebeauty.com')

@section('headline2', 'YOUR ACCOUNT IS NOW ACTIVE')

@section('bold-text')
	We're excited to bring you a brand new way to let your finances flow without interruption.
	You can now begin sending money to anyone, anywhere.
@endsection

@section('text')
	You can register a receiving account in-app and begin receiving money from everyone!
	<br><br>
	At NR we're built for you, with everything open to you to give you the best platform possible.
@endsection