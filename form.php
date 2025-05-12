<!DOCTYPE html>
<html>
<head>
    <title>PhonePe Payment</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-6 offset-md-3">
                <div class="card">
                    <div class="card-header">
                        <h4>Payment Details</h4>
                    </div>
                    <div class="card-body">
                        <?php if($this->session->flashdata('error')): ?>
                            <div class="alert alert-danger">
                                <?php echo $this->session->flashdata('error'); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form action="<?php echo base_url('phonepe/initiate_payment'); ?>" method="post">
                            <div class="form-group">
                                <label>Amount (₹)</label>
                                <input type="number" name="amount" class="form-control" required min="1" step="0.01">
                            </div>
                            
                            <div class="form-group">
                                <label>Mobile Number</label>
                                <input type="text" name="mobile" class="form-control" required pattern="[0-9]{10}">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Pay with PhonePe</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 