<!DOCTYPE html>
<html>
<head>
    <title>Payment Failed</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-6 offset-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-times-circle text-danger" style="font-size: 48px;"></i>
                        <h3 class="mt-3">Payment Failed</h3>
                        <p>Sorry, your payment could not be processed.</p>
                        <?php if($this->session->flashdata('error')): ?>
                            <div class="alert alert-danger">
                                <?php echo $this->session->flashdata('error'); ?>
                            </div>
                        <?php endif; ?>
                        <a href="<?php echo base_url('payment/form'); ?>" class="btn btn-primary">Try Again</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://kit.fontawesome.com/your-font-awesome-kit.js"></script>
</body>
</html> 