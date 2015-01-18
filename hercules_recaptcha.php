<?php
/*
Plugin Name: Herc Recaptcha
Description: Adds a recaptcha with the new Google Recaptcha API to the comment form for anonymous users
Author: Todd D. Nestor - todd.nestor@gmail.com
Version: 1.0
License: GNU General Public License v3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

/**
 * Class HercRecaptcha adds Recaptcha to the specified forms.
 *
 * The new Google Recaptcha API still uses javascript, but the check will fail if the item isn't submitted,
 * that is the form is useless with javascript disabled.
 */
    class HercRecaptcha
    {

        /**
         * This constructor loads the scripts for Google's Recaptcha and adds the Recaptcha form to the comments form.
         */
        function __construct()
        {

            $this->SetRecaptchaKeys();
            $current_theme = get_current_theme();
            
            add_action( 'admin_menu', array( $this, 'AddSettingsPage' ) );
            
            if( $this->comment_form != 'false' )
            {
                add_action( $this->placement, array( $this, 'RenderRecaptcha' ) );
                add_filter( 'preprocess_comment', array( $this, 'VerifyCommentRecaptcha' ) );
            }
            
            if( $this->registration_form != 'false' )
            {
                add_action( 'signup_extra_fields', array( $this, 'RenderRecaptcha' ) );
                if( is_multisite() )
                    add_filter( 'wpmu_validate_user_signup', array( $this, 'VerifyRegistrationRecaptcha' ) );
                else
                    add_filter( 'registration_errors', array( $this, 'VerifyRegistrationRecaptcha' ) );
            }
            add_action( 'wp_head', array( $this, 'AddRecaptchaSnippet' ) );
            add_action( 'wp_enqueue_scripts', array( $this, 'AddRecaptchaScript' ) );
        }
        
        function TestRegistrationForm()
        {
            echo 'i am right here!!!!!!!!!!!!';
        }
        
        /**
         * Creates the array of placement options with the hook as the value
         * @return array  The hook that will get used is the key, the value is the label for the form
         */
        function PlacementOptions()
        {
            $array = array(
                'comment_form_before'           => "Before the form",
                'comment_form_top'              => "After the form title",
                'comment_form_before_fields'    => "Right before the form fields",
                'comment_form_after_fields'     => "After the author fields",
                'comment_form'                  => "After the submit button",
                'comment_form_after'            => "After the entire comment form",
            );
            
            return $array;
        }
        
        /**
         * Generates the HTML for the settings page which shows up as a submenu of "Settings" in wp-admin
         *
         * @todo Provide options for where the Recaptcha shows up
         * $todo Provide an option for light or dark theme for the recatpcha
         */
        function GenerateSettingsPage()
        {
            if( isset( $_POST["update_herc_recaptcha_settings"] ) )
            {
                update_option( 'herc_recaptcha_options', $_POST['herc_recaptcha_options'] );
                $success_msg = "Recaptcha Keys Updated";
            }
            $this->SetRecaptchaKeys();
            $placement_options = $this->PlacementOptions();
            ?>
                <div class="wrap">
                    <h2>Recaptcha Settings</h2>
                        <table class="form-table">
                            <?php if( !empty( $success_msg ) ): ?>
                            <tr valign="top">
                                <th colspan="2" scope="row">
                                    <span style="color:red; font-weight:bold;"><?php echo $success_msg; ?></span>
                                </th>
                            </tr>
                            <?php endif; ?>
                            <tr valign="top">
                                <th colspan="2" scope="row">
                                    If you don't have these keys yet go to <a href="https://www.google.com/recaptcha/" target="_blank">https://www.google.com/recaptcha/</a> to obtain keys.
                                    <br><br>
                                    If the Recaptcha isn't showing up try a different placement.  Also, some other comment plugins (such as Jetpack comments) interfere with this plugin and they will not work together.
                                </th>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <form method="POST" action="">
                                    <input type="hidden" name="update_herc_recaptcha_settings" value="Y" />
                                    <label for="herc_recaptcha_options[public_key]">
                                        Site Key
                                    </label> 
                                </th>
                                <td>
                                    <input type="text" name="herc_recaptcha_options[public_key]" size="25" value="<?php echo $this->public_recaptcha_key;?>" />
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <label for="herc_recaptcha_options[private_key]">
                                        Secret Key
                                    </label> 
                                </th>
                                <td>
                                    <input type="text" name="herc_recaptcha_options[private_key]" size="25" value="<?php echo $this->private_recaptcha_key;?>" />
                                </td>
                            </tr>
                            <tr valign="top">
                                <th colspan="2" scope="row">
                                    <h3>
                                        Comment Form
                                    </h3> 
                                </th>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <label for="herc_recaptcha_options[registration_form]">
                                        Show on Comment Form
                                    </label>
                                </th>
                                <td>
                                    <input type="hidden" name="herc_recaptcha_options[comment_form]" value="false" />
                                    <input type="checkbox" name="herc_recaptcha_options[comment_form]" value="true" <?php echo $this->comment_form == 'false' ? '' : 'checked="checked"'; ?> />
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <label for="herc_recaptcha_options[placement]">
                                        Location: 
                                    </label> 
                                </th>
                                <td>
                                    <select name="herc_recaptcha_options[placement]">
                                        <?php
                                        foreach( $placement_options as $key=>$val )
                                        {
                                        ?>
                                        <option value="<?php echo $key; ?>" <?php echo $key == $this->placement ? "selected" : ""; ?>><?php echo $val; ?></option>
                                        <?php
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th colspan="2" scope="row">
                                    <h3>
                                        Registration Form
                                    </h3> 
                                </th>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <label for="herc_recaptcha_options[registration_form]">
                                        Show on Registration Form
                                    </label>
                                </th>
                                <td>
                                    <input type="hidden" name="herc_recaptcha_options[registration_form]" value="false" />
                                    <input type="checkbox" name="herc_recaptcha_options[registration_form]" value="true" <?php echo $this->registration_form == 'false' ? '' : 'checked="checked"'; ?> />
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <label for="herc_recaptcha_options[placement]">
                                        Style: 
                                    </label> 
                                </th>
                                <td>
                                    <select name="herc_recaptcha_options[style]">
                                        <?php
                                        $styles = array(
                                            'dark'      => 'Dark',
                                            'light'     => 'Light',
                                        );
                                        foreach( $styles as $key=>$val )
                                        {
                                        ?>
                                        <option value="<?php echo $key; ?>" <?php echo $key == $this->style ? "selected" : ""; ?>><?php echo $val; ?></option>
                                        <?php
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    
                                </th>
                                <td>
                                    <input type="submit" />
                                    </form>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    
                                </th>
                                <td>
                                    <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
                                        <input type="hidden" name="cmd" value="_s-xclick">
                                        <input type="hidden" name="hosted_button_id" value="GDBHPL4Y24ZXQ">
                                        <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
                                        <img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
                                    </form>
                                </td>
                            </tr>
                    </table>
                </div>
            <?php
        }
        
        /**
         * Adds the settings page as a submenu of "Settings"
         */
        function AddSettingsPage()
        {
            add_options_page( 'Hercules Recaptcha', 'Hercules Recaptcha', 'manage_options', 'hercules-recaptcha', array( $this, 'GenerateSettingsPage' ) );
        }

        /**
         * @return array|void Recaptcha options as an array from the database.
         */
        function GetRecaptchaSettings()
        {
            return get_option( 'herc_recaptcha_options' );
        }

        /**
         * Sets the public and private Recaptcha keys to be used in this plugin.
         */
        function SetRecaptchaKeys()
        {
            $herc_recaptcha_options = $this->GetRecaptchaSettings();

            $publickey  = $herc_recaptcha_options['public_key'];
            $privatekey = $herc_recaptcha_options['private_key'];
            $placement  = !empty( $herc_recaptcha_options['placement'] ) ? $herc_recaptcha_options['placement'] : 'comment_form_after_fields';
            $style      = !empty( $herc_recaptcha_options['style'] ) ? $herc_recaptcha_options['style'] : 'dark';
            $registration_form = !empty( $herc_recaptcha_options['registration_form'] ) ? $herc_recaptcha_options['registration_form'] : 'true';
            $comment_form = !empty( $herc_recaptcha_options['comment_form'] ) ? $herc_recaptcha_options['comment_form'] : 'true';

            $this->public_recaptcha_key = $publickey;
            $this->private_recaptcha_key = $privatekey;
            $this->placement = $placement;
            $this->style = $style;
            $this->registration_form = $registration_form;
            $this->comment_form = $comment_form;
        }

        /**
         * This function prints out the Recaptcha javascript call that the Google API will use.
         */
        function AddRecaptchaSnippet()
        {
            echo "
            <script type=\"text/javascript\">
                var onloadCallback = function() {
                var recaptchaElement = document.getElementById('recaptcha-comment');
                if( recaptchaElement != null )
                {
                    grecaptcha.render('recaptcha-comment', {
                          'sitekey' : '$this->public_recaptcha_key',
                          'theme' : '$this->style',
                          'hl' : 'en'
                        });
                }
            };
            </script>";
        }

        /**
         * This function will enqueue Google's Recaptcha js file.
         */
        function AddRecaptchaScript()
        {
            wp_enqueue_script( 'google-recaptcha', 'https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit', array(), '1.0', true );
        }

        /**
         * Renders the element that Google's Recaptcha turns into the Recaptcha.
         */
        function RenderRecaptcha()
        {
            $this->SetRecaptchaKeys();
            
            if( is_user_logged_in() || empty( $this->public_recaptcha_key ) || empty( $this->private_recaptcha_key ) )
                return;

            if( !empty( $_GET['recaptcha'] ) )
            {
                if( $_GET['recaptcha'] == 'notchecked' )
                    echo '<div class="recaptcha-notchecked">You need to click captcha checkox.</div>';
                else
                    echo '<div class="recaptcha-invalid">The captcha response is not valid.</div>';
            }
            echo '<div id="recaptcha-comment" class="txtHide"></div>';
        }

        /**
         * This function is used to verify the Recaptcha submitted from a comment form.
         *
         * @param mixed $commentdata Wordpress passes in the comment data submitted from the comment form, this verifies the Recaptcha.
         */
        function VerifyCommentRecaptcha( $commentdata )
        {
            if( is_user_logged_in() || empty( $this->public_recaptcha_key ) || empty( $this->private_recaptcha_key ) )
                return $commentdata;

            if (empty($_POST['g-recaptcha-response']))
            {
                $url = get_permalink( $commentdata['comment_post_ID']) . '?recaptcha=notchecked';
                wp_redirect( $url );
                exit;
            }
            else
            {
                /* Get Recaptcha keys to use */
                $gglcptch_options = $this->GetRecaptchaSettings();

                $captcha_url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . $this->private_recaptcha_key . '&response=' . $_POST['g-recaptcha-response'] . '&remoteip=' . $_SERVER['REMOTE_ADDR'];
                if ( $data = wp_remote_get( $captcha_url ) )
                {
                    $obj = json_decode( $data['body'] );
                    if ( $obj->success )
                    {
                        return $commentdata;
                    }
                    else
                    {

                        $url = get_permalink( $commentdata['comment_post_ID']) . '?recaptcha=' . $this->private_recaptcha_key;
                        wp_redirect($url);
                        exit;
                    }
                }
                else
                {
                    wp_die(__('Can\'t return the captcha repsonse.'));
                }
            }
        }
        
        function VerifyRegistrationRecaptcha( $registrationdata )
        {
            if( is_user_logged_in() || empty( $this->public_recaptcha_key ) || empty( $this->private_recaptcha_key ) )
                return $registrationdata;

            if (empty($_POST['g-recaptcha-response']))
            {
                $registrationdata['errors']->add( 'blank_captcha', '<strong>You must check the Recaptcha</strong>' );
                return $registrationdata;
            }
            else
            {
                /* Get Recaptcha keys to use */
                $gglcptch_options = $this->GetRecaptchaSettings();

                $captcha_url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . $this->private_recaptcha_key . '&response=' . $_POST['g-recaptcha-response'] . '&remoteip=' . $_SERVER['REMOTE_ADDR'];
                if ($data = wp_remote_get( $captcha_url ) )
                {
                    $obj = json_decode($data['body']);
                    if ($obj->success)
                    {
                        return $registrationdata;
                    }
                    else
                    {
                        
                    }
                }
                else
                {
                    wp_die(__('Can\'t return the captcha repsonse.'));
                }
            }
        }
        
        function RegistrationCaptcha()
        {
            if( $this->is_multi_blog() )
            {
                add_filter( 'wpmu_validate_user_signup', array( $this, 'VerifyRegistrationRecaptcha' ) );
            }
            else
            {
                add_filter( 'registration_errors', array( $this, 'VerifyRegistrationRecaptcha' ) );
            }
        }
        
        protected function is_multi_blog()
        {
            return is_multisite();
        }
    }
    
$herc_recaptcha = new HercRecaptcha;

?>