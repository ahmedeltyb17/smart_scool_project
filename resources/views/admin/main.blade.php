@include('admin.layouts.head')
    <div class="container-scroller">
    <!-- partial:partials/_sidebar.html -->
    @include('admin.layouts.sidebar')
    <!-- partial -->
    <div class="container-fluid page-body-wrapper">
        <!-- partial:partials/_navbar.html -->
        @include('admin.layouts.navbar')
        <!-- partial -->
        <div class="main-panel">
            <div class="content-wrapper">
                @yield('body')
                    @include('admin.layouts.footer')
