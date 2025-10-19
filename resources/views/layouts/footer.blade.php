@section('footer')
    <div class="float-right d-none d-sm-block">
        <b>Version</b> @yield('version', config('app.version', '1.0'))
    </div>
    <strong>Copyright &copy; 2025 <a href="https://rihac.xyz">RiHac Technology</a>.</strong> All rights
    reserved.
@endsection
