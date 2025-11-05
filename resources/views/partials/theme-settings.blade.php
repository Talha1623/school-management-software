<!-- Start Theme Setting Area -->
<button class="btn btn-primary theme-settings-btn p-0 position-fixed z-2 text-center rounded-circle" style="bottom: 24px; right: 24px; width: 56px; height: 56px; line-height: 54px;" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasScrolling" aria-controls="offcanvasScrolling">
    <i class="text-white ri-settings-3-fill fs-28" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Click On Theme Settings"></i>
</button>

<!-- Start Theme Setting Area -->
<div class="offcanvas offcanvas-end bg-white border-0" data-bs-scroll="true" data-bs-backdrop="true" tabindex="-1" id="offcanvasScrolling" aria-labelledby="offcanvasScrollingLabel" style="box-shadow: 0 4px 20px #2f8fe812 !important; max-width: 300px;">
    <div class="offcanvas-header bg-light p-20">
        <h5 class="offcanvas-title fs-18 fw-medium" id="offcanvasScrollingLabel">Configuration Panel</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0 overflow-hidden">
        <div class="last-child-none" style="max-height: 858px;" data-simplebar>
            <div class="p-20 border-bottom child">
                <h4 class="fs-15 fw-medium mb-12">RTL Mode</h4>
                <div class="rtl-btn">
                    <label id="switch">
                        <input type="checkbox" onchange="toggleTheme()" class="toggle-switch rtl-switch" id="slider">
                    </label>
                </div>
            </div>
            <div class="p-20 border-bottom child">
                <h4 class="fs-15 fw-medium mb-12">Only Sidebar Dark</h4>
                <div class="sidebar-light-dark" id="sidebar-light-dark">
                    <input type="checkbox" class="toggle-switch sidebar-dark-switch">
                </div>
            </div>
            <div class="p-20 border-bottom child">
                <h4 class="fs-15 fw-medium mb-12">Only Header Dark</h4>
                <div class="header-light-dark" id="header-light-dark">
                    <input type="checkbox" class="toggle-switch header-dark-switch">
                </div>
            </div>
            <div class="p-20 border-bottom child">
                <h4 class="fs-15 fw-medium mb-12">Right Sidebar</h4>
                <div class="right-sidebar" id="right-sidebar">
                    <input type="checkbox" class="toggle-switch right-sidebar-switch">
                </div>
            </div>
            <div class="p-20 border-bottom child">
                <h4 class="fs-15 fw-medium mb-12">Hide Sidebar</h4>
                <div class="icon-sidebar" id="icon-sidebar">
                    <input type="checkbox" class="toggle-switch icon-sidebar-switch">
                </div>
            </div>
            <div class="p-20 border-bottom child">
                <h4 class="fs-15 fw-medium mb-12">Bordered Card</h4>
                <div class="card-border" id="card-border">
                    <input type="checkbox" class="toggle-switch border-switch">
                </div>
            </div>
            <div class="p-20 border-bottom child">
                <h4 class="fs-15 fw-medium mb-12">Card Border Radius</h4>
                <div class="card-radius-square" id="card-radius-square">
                    <input type="checkbox" class="toggle-switch border-radius-switch">
                </div>
            </div>
            
            <div class="p-20 border-bottom child">
                <a href="#" class="btn btn-primary text-white">
                    Buy Fila
                </a>
            </div>
        </div>
    </div>
</div>
<!-- End Theme Setting Area -->

