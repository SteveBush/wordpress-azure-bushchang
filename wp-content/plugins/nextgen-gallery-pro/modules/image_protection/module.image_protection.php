<?php

/***
	{
		Module: photocrati-image_protection,
		Depends: { photocrati-nextgen_other_options }
	}
***/

class M_Photocrati_Image_Protection extends C_Base_Module
{
    function define()
    {
        parent::define(
            'photocrati-image_protection',
            'Protect Images',
            'Protects images from being stored locally by preventing right clicks and drag & drop of the images',
            '0.16',
            'http://www.photocrati.com',
            'Photocrati Media',
            'http://www.photocrati.com'
        );

        C_Photocrati_Installer::add_handler($this->module_id, 'C_Image_Protection_Installer');
    }

    function _register_hooks()
    {
        add_action('wp_enqueue_scripts', array($this, '_register_protection_js'));
        add_action('admin_init', array($this, 'register_forms'));
	    add_action('ngg_created_new_gallery', array(&$this, 'protect_gallery'));
    }

    function _register_adapters()
    {
        if (is_admin())
            $this->get_registry()->add_adapter('I_Form', 'A_Image_Protection_Form', 'image_protection');
        else
            $this->get_registry()->add_adapter('I_Display_Type_Controller', 'A_Image_Protection_Effect_Code');
    }

    function register_forms()
    {
        $forms = C_Form_Manager::get_instance();
        $forms->add_form(NGG_OTHER_OPTIONS_SLUG, 'image_protection');
    }

    function _register_protection_js()
    {
        if (!is_admin() && C_NextGen_Settings::get_instance()->protect_images)
        {
            $router = C_Router::get_instance();

            $handle = 'pressure';
            $do_register = TRUE;
            if (wp_script_is('pressure', 'registered')) {
                $do_register = FALSE;
            }
            else if (wp_script_is('pressurejs', 'registered')) {
                $handle = 'pressurejs';
                $do_register = FALSE;
            }

            if ($do_register) wp_register_script(
                $handle,
                $router->get_static_url('photocrati-image_protection#pressure.js'),
                array('jquery')
            );

            wp_register_script(
                'photocrati-image_protection-js',
                $router->get_static_url('photocrati-image_protection#custom.js'),
                array('jquery', $handle)
            );
            wp_enqueue_script('photocrati-image_protection-js');

            wp_enqueue_style(
                'photocrati-image_protection-css',
                $router->get_static_url('photocrati-image_protection#custom.css')
            );

            wp_localize_script(
                'photocrati-image_protection-js',
                'photocrati_image_protection_global',
                array('enabled' => C_NextGen_Settings::get_instance()->protect_images_globally)
            );
        }
    }

	function protect_gallery($gallery_id)
	{
		$imgprot = C_Image_Protection_Manager::get_instance();
		$imgprot->protect_gallery($gallery_id);
	}

	function get_type_list()
	{
		return array(
			'A_Image_Protection_Effect_Code' => 'adapter.image_protection_effect_code.php',
			'A_Image_Protection_Form'        => 'adapter.image_protection_form.php',
			'C_Image_Protection_Manager'    =>  'class.image_protection_manager.php'
		);
	}
}

class C_Image_Protection_Installer
{
    function install()
    {
        $settings = C_NextGen_Settings::get_instance();
        $this->install_image_protection_settings($settings);
    }

    function install_image_protection_settings($settings)
    {
        $settings->set_default_value('protect_images', 0);
        $settings->set_default_value('protect_images_globally', 0);
    }
}

new M_Photocrati_Image_Protection();