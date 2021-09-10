
<div class="row d-block mb-5 mt-5" style="text-align:center;">
    
    <div class="login-box col-md-4" style="display:inline-block">
        
        <!-- /.login-logo -->
        <div class="card card-outline card-outline-tabs">
            <div class="card-body login-card-body">
                
                <h3 class="login-box-msg">Change Password</h3>
                
                <form action="{{ url('change-password') }}" method="post" oninput='password_confirmation.setCustomValidity(password_confirmation.value != password.value ? "Passwords do not match." : "")'>
                    @csrf
                    <input type="hidden" name="token" value="{{@$token}}">
                    <div id="password_block">
                     
                        <div class="input-group mb-3" >
                            <input type="email" class="form-control" name="email" value="{{ $email }}" placeholder="Email" required readonly>
                        </div>
                     
                        <div class="input-group mb-3" >
                
                           <input type="password" name="password" placeholder="Password" class="form-control" id="password" required="">
                           @if($errors->has('password'))
                           <span class="invalid-feedback" style="display: block;text-align:left;">
                               {{ $errors->first('password') }}
                           </span>
                       @endif
                        </div>
                     
                        <div class="input-group mb-3" >
                            <input type="password" name="password_confirmation" placeholder="Confirm Password" class="form-control" id="confirm_password" required="">

                        </div>
                       

                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary btn-block login mb-4">Change Password</button>
                            </div>
                            <!-- /.col -->
                        </div>
                    </div>
                    
                </form>
                
                
                <!-- /.social-auth-links -->
                
                
            </div>
            <!-- /.login-card-body -->
        </div>
    </div>
    
</div>
