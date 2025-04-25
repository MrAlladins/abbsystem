<header>
    <div class="header-container">
        <div class="logo">
            <h1>Abonnemangssystem</h1>
        </div>
        
        <nav>
            <ul>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                        <li><a href="admin_dashboard.php">Admin Dashboard</a></li>
                        <li><a href="admin_users.php">Användare</a></li>
                        <li><a href="admin_customers.php">Alla kunder</a></li>
                        <li><a href="admin_subscriptions.php">Alla abonnemang</a></li>
                    <?php else: ?>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="customers.php">Kunder</a></li>
                        <li><a href="subscriptions.php">Abonnemang</a></li>
                        <li><a href="subscription_plans.php">Abonnemangstyper</a></li>
                        <li><a href="payments.php">Betalningar</a></li>
                    <?php endif; ?>
                    <li><a href="profile.php">Min profil</a></li>
                    <li><a href="logout.php">Logga ut</a></li>
                <?php else: ?>
                    <li><a href="login.php">Logga in</a></li>
                    <li><a href="register.php">Registrera</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>
