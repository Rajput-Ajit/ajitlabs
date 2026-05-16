<?php
    class Components{

        public static function sidebar($page){

            $active = function($p) use ($page) {
                return $page === $p ? 'nl on' : 'nl';
            };

            return '
            <div class="p-5 border-b border-amber-900/20 flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center text-base font-black text-stone-900 flex-shrink-0" style="background:linear-gradient(135deg,#c9a84c,#e8b84b)">R</div>
                    <div>
                        <div class="display text-amber-300 font-bold text-lg leading-none">ReadSpace</div>
                        <div class="text-stone-500 text-xs">Admin Panel</div>
                    </div>
                </div>
            </div>

            <nav class="p-3 flex-1">
                <div class="text-xs text-stone-600 uppercase tracking-widest px-3 mb-2 mt-1">Main</div>

                <a href="admin-dashboard.php" class="'.$active('dashboard').'">⊞ <span>Dashboard</span></a>
                <a href="admin-halls.php" class="'.$active('halls').'">🏛 <span>Manage Halls</span></a>
                <a href="admin-seats.php" class="'.$active('seats').'">🪑 <span>Seat Manager</span></a>
                <a href="admin-students.php" class="'.$active('students').'">👥 <span>Students</span></a>
                <a href="admin-fees.php" class="'.$active('fees').'">💳 <span>Fees & Payments</span></a>

                <div class="text-xs text-stone-600 uppercase tracking-widest px-3 mb-2 mt-4">Config</div>

                <a href="admin-settings.php" class="'.$active('settings').'">⚙ <span>Settings</span></a>
            </nav>

            <div class="p-4 border-t border-amber-900/10 flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold text-stone-900 flex-shrink-0" style="background:linear-gradient(135deg,#c9a84c,#e8b84b)">RS</div>
                    <div class="flex-1 min-w-0">
                        <div class="text-xs text-stone-200 font-medium truncate">Rahul Sharma</div>
                        <div class="text-xs text-stone-500">Super Admin</div>
                    </div>
                    <a href="admin-login.php" class="text-stone-500 hover:text-red-400 text-lg" title="Logout">⏻</a>
                </div>
            </div>
            ';
        }

        public static function sidebarComplete(){
            return '
                <div class="p-5 border-b border-amber-900/20 flex-shrink-0">
                    <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center text-base font-black text-stone-900 flex-shrink-0" style="background:linear-gradient(135deg,#c9a84c,#e8b84b)">R</div>
                    <div>
                        <div class="display text-amber-300 font-bold text-lg leading-none">ReadSpace</div>
                        <div class="text-stone-500 text-xs">Admin Panel</div>
                    </div>
                    </div>
                </div>
                <div class="px-4 py-3 border-b border-amber-900/10 flex-shrink-0">
                    <select class="w-full text-xs px-3 py-2 rounded-lg text-amber-300" style="background:rgba(201,168,76,0.1);border:1px solid rgba(201,168,76,0.2)">
                    <option>🏛 Nagpur Main Branch</option>
                    <option>🏛 Civil Lines Branch</option>
                    <option>🏛 Sitabuldi Branch</option>
                    </select>
                </div>
                <nav class="p-3 flex-1">
                    <div class="text-xs text-stone-600 uppercase tracking-widest px-3 mb-2 mt-1">Main</div>
                    <a href="admin-dashboard.html" class="nl on">⊞ <span>Dashboard</span></a>
                    <a href="admin-halls.html" class="nl">🏛 <span>Manage Halls</span></a>
                    <a href="admin-seats.html" class="nl">🪑 <span>Seat Manager</span></a>
                    <a href="admin-students.html" class="nl">👥 <span>Students</span></a>
                    <a href="admin-fees.html" class="nl">💳 <span>Fees & Payments</span></a>
                    <div class="text-xs text-stone-600 uppercase tracking-widest px-3 mb-2 mt-4">Config</div>
                    <a href="admin-branches.html" class="nl">📍 <span>Branches</span></a>
                    <a href="admin-shifts.html" class="nl">⏰ <span>Shift Timings</span></a>
                    <a href="admin-subscription.html" class="nl">⭐ <span>Subscription</span></a>
                    <a href="admin-settings.html" class="nl">⚙ <span>Settings</span></a>
                    <div class="text-xs text-stone-600 uppercase tracking-widest px-3 mb-2 mt-4">My Plan</div>
                    <a href="saas-overview.html" class="nl">🚀 <span>Plan Overview</span></a>
                    <a href="saas-billing.html" class="nl">🧾 <span>Billing &amp; Invoices</span></a>
                    <a href="saas-upgrade.html" class="nl">⬆ <span>Upgrade Plan</span></a>
                    <a href="saas-usage.html" class="nl">📊 <span>Usage &amp; Limits</span></a>
                </nav>
                <div class="p-4 border-t border-amber-900/10 flex-shrink-0">
                    <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold text-stone-900 flex-shrink-0" style="background:linear-gradient(135deg,#c9a84c,#e8b84b)">RS</div>
                    <div class="flex-1 min-w-0">
                        <div class="text-xs text-stone-200 font-medium truncate">Rahul Sharma</div>
                        <div class="text-xs text-stone-500">Super Admin</div>
                    </div>
                    <a href="admin-login.html" class="text-stone-500 hover:text-red-400 text-lg" title="Logout">⏻</a>
                    </div>
                </div>
            ';
        }
    }
?>