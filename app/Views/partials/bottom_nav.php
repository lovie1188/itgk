<!-- ============================================================ -->
<!-- NATIVE ANDROID BOTTOM NAVIGATION BAR (Mobile Screens Only)   -->
<!-- ============================================================ -->
<nav class="native-mobile-bottom-nav d-md-none fixed-bottom">
    <div class="container-fluid px-1">
        <div class="row text-center g-0 align-items-center">
            <!-- 1. Home / Dashboard -->
            <div class="col">
                <a href="<?= BASE_URL ?>dashboard" class="mobile-nav-item <?= (strpos($_SERVER['REQUEST_URI'], '/dashboard') !== false || $_SERVER['REQUEST_URI'] === BASE_URL || $_SERVER['REQUEST_URI'] === rtrim(BASE_URL, '/')) ? 'active' : '' ?>">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
            </div>
            <!-- 2. ITGK Modules -->
            <div class="col">
                <a href="<?= BASE_URL ?>itgk/details" class="mobile-nav-item <?= strpos($_SERVER['REQUEST_URI'], '/itgk/details') !== false || strpos($_SERVER['REQUEST_URI'], '/itgk/admissions') !== false ? 'active' : '' ?>">
                    <i class="fas fa-building"></i>
                    <span>ITGK</span>
                </a>
            </div>
            <!-- 3. Primary Action FAB (Center Button for Books / Quick New Transaction) -->
            <div class="col">
                <a href="<?= BASE_URL ?>books/list" class="mobile-fab-item <?= strpos($_SERVER['REQUEST_URI'], '/books') !== false ? 'active' : '' ?>" title="Books Management">
                    <div class="fab-circle">
                        <i class="fas fa-book"></i>
                    </div>
                    <span>Books</span>
                </a>
            </div>
            <!-- 4. Certificates -->
            <div class="col">
                <a href="<?= BASE_URL ?>itgk/list" class="mobile-nav-item <?= strpos($_SERVER['REQUEST_URI'], '/itgk/list') !== false || strpos($_SERVER['REQUEST_URI'], '/certificates') !== false ? 'active' : '' ?>">
                    <i class="fas fa-certificate"></i>
                    <span>Certificates</span>
                </a>
            </div>
            <!-- 5. Learners -->
            <div class="col">
                <a href="<?= BASE_URL ?>learners/list" class="mobile-nav-item <?= strpos($_SERVER['REQUEST_URI'], '/learners') !== false ? 'active' : '' ?>">
                    <i class="fas fa-user-graduate"></i>
                    <span>Learners</span>
                </a>
            </div>
        </div>
    </div>
</nav>

<style>
/* Native Android Material Bottom Navigation Styling */
.native-mobile-bottom-nav {
    z-index: 1045;
    background: rgba(255, 255, 255, 0.96);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-top: 1px solid rgba(0, 0, 0, 0.08);
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08);
    padding: 4px 0 6px 0;
}

.mobile-nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #64748b;
    text-decoration: none !important;
    padding: 3px 0;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.mobile-nav-item i {
    font-size: 1.15rem;
    margin-bottom: 2px;
    transition: transform 0.2s ease;
}

.mobile-nav-item span {
    font-size: 10px;
    font-weight: 500;
}

.mobile-nav-item.active {
    color: #1e3a8a;
    font-weight: 700;
}

.mobile-nav-item.active i {
    transform: translateY(-2px) scale(1.1);
    color: #1e3a8a;
}

/* Center Floating Action Button (FAB) Style */
.mobile-fab-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-decoration: none !important;
    color: #64748b;
    margin-top: -14px;
}

.fab-circle {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(30, 58, 138, 0.35);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.fab-circle i {
    font-size: 1.2rem;
}

.mobile-fab-item span {
    font-size: 10px;
    font-weight: 600;
    margin-top: 2px;
}

.mobile-fab-item.active .fab-circle {
    transform: scale(1.1);
    box-shadow: 0 6px 16px rgba(30, 58, 138, 0.5);
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

@media (max-width: 767.98px) {
    body {
        padding-bottom: 68px !important;
    }
}
</style>
