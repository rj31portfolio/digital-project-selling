    </main>

    <footer class="bg-dark text-white py-4 mt-4">
        <div class="container">
            <div class="row">
                <!-- About Section -->
                <div class="col-md-4">
                    <h5>About Us</h5>
                    <p>Digital Project Store is a platform for buying and selling high-quality digital projects, templates, and source code.</p>
                </div>

                <!-- Quick Links -->
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo BASE_URL; ?>" class="text-white">Home</a></li>
                        <li><a href="<?php echo BASE_URL; ?>?featured=1" class="text-white">Featured Projects</a></li>
                        <li><a href="#" class="text-white">Terms of Service</a></li>
                        <li><a href="#" class="text-white">Privacy Policy</a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div class="col-md-4">
                    <h5>Contact</h5>
                    <address>
                        <?php echo htmlspecialchars($functions->getSetting('company_name')); ?><br>
                        <?php echo nl2br(htmlspecialchars($functions->getSetting('company_address'))); ?>
                    </address>
                </div>
            </div>

            <hr>

            <!-- Copyright -->
            <div class="text-center">
                <p>&copy; <?php echo date('Y'); ?> Digital Project Store. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>

    <?php if (isset($additionalScripts) && is_array($additionalScripts)): ?>
        <?php foreach ($additionalScripts as $script): ?>
            <script src="<?php echo htmlspecialchars($script); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
