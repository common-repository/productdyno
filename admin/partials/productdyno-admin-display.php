<?php
   /**
    * Provide a admin area view for the plugin
    *
    * This file is used to markup the admin-facing aspects of the plugin.
    *
    * @link       productdyno.com
    * @since      1.0.0
    *
    * @package    Productdyno
    * @subpackage Productdyno/admin/partials
    */
?>
<div class="productdyno-main-container">
   <!-- HEADER -->
   <header class="block">
      <div class="productdyno_logo">
         <a href="?page=productdyno"><img src="<?php echo plugins_url('images/logo.png', dirname(__FILE__));  ?>"></a>
      </div>
      <!-- <hr> -->
      <?php if(!$_GET['act'] && !$_GET['help']): ?>
      <div class="productdyno_page_title">
         <div class="start_message">
            <h1>Welcome to ProductDyno</h1>
            <p>This plugin allows you to directly connect your ProductDyno account without any hassle. </p>
            <div class="content-area">
               <div class="dashboard-menu">
                  <ul>
                     <?php if(!empty($api_key)): ?>
                     <li><a href="?page=productdyno&act=1"><i class="fa fa-lock"></i>Connected</a></li>
                     <?php else: ?>
                     <li><a href="?page=productdyno&act=1"><i class="fa fa-unlock"></i>Connect</a></li>
                     <?php endif; ?>  
                     <li><a href="?page=productdyno&help=1"><i class="fa fa-support"></i>Help & Support</a></li>
                     <li style="display: none !important;" class="pdClearingCacheData"><a><i class="fa fa-spinner fa-spin"></i>Clearing Data... </a></li>
                     <li class="pdClearData"><a href="javascript:void(0);" class="pdClearAllCahceData"><i class="fa fa-database"></i>Clear Cache Data </a></li>
                     <li><a><i class="fa fa-code-fork"></i>Version <?php echo PRODUCTDYNO_PLUGIN_VERSION; ?> </a></li>
                  </ul>
               </div>
            </div>
         </div>
      </div>
      <?php elseif ($_GET['help'] && $_GET['help'] == 1 && !$_GET['act']): ?>
      <div class="productdyno_page_title">
         <div class="start_message">
            <div class="back-btn">
               <a href="?page=productdyno" style="font-weight: bold;"><i class="fa fa-chevron-left""></i> BACK</a>
            </div>
            <h1>Need help with something?</h1>
            <p>We have a handful of knowledge base articles, check them out at:<br> <a href="https://docs.promotelabs.com/category/747-productdyno" target="_blank">Knowledge Base</a></p>
            <p>To create a ticket at our support desk send an email to <strong>"help@promotelabs.com"</strong>. All emails automatically become help tickets.<br> Please be patient...we answer every ticket!.
            </p>
         </div>
      </div>
      <?php elseif($_GET['act'] && $_GET['act'] == 1 && !$_GET['help']): ?>
      <div class="productdyno_page_title">
         <div class="start_message">
            <div class="back-btn">
               <a href="?page=productdyno" style="font-weight: bold;"><i class="fa fa-chevron-left""></i> BACK</a>
            </div>
            <h1>Connect your Account</h1>
            <p>Enter your API key below to connect your ProductDyno account with your website. You may find your API key inside your ProductDyno account.</p>
            <div class="activate-area">
               <div style="width: 50%; float: left;">
                  <div class="content-area">
                     <div class="productdyno-mt-20">
                        <div class="productdyno-form">
                           <?php if($_GET['res'] && $_GET['res'] == 'inv'): ?>
                            <p style="color: red;">API Key is not valid, please use valid key.</p>
                           <?php endif; ?>
                           <form class="form-label form-css-label productdyno-mt-20" method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
                              <input type="hidden" name="action" value="pd_verify_api_key">
                              <fieldset>
                                 <input id="" name="_productdyno_api_key" type="text" value="<?php echo ($api_key ? $api_key : null) ?>" style="border: 1px solid #e0dede; padding-top: 35px;" required />
                                 <label for="" style="top: 10px; left: 10px; font-weight: bold;">API Key </label>
                              </fieldset>
                              <button class="productdyno-mt-10 productdyno-button productdyno-button-small productdyno-button-block productdyno-form-submit-license" type="submit">Update</button>
                           </form>
                           <?php if(!empty($api_key)): ?>
                             <div style="float: right; " class="productdyno-mt-10">
                                <a href="?page=productdyno&act=1&res=<?php echo $_GET['res']; ?>&_pd_deactivate=1" style="color: red;" onclick="return confirm('Are you sure you want to disconnect ProductDyno plugin?')" >Disconnect</a>
                               
                             </div>
                           <?php endif;?>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
      <?php else: ?>
      <p>Not Found!</p>
      <?php endif; ?>
      <!-- <hr> -->
   </header>
</div>