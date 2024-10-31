<?php

class nuajik_url_rewrite_hook
{

    private $cdn_public_url = '';
    private $wordpress_instance_url = '';

    public function __construct($wordpress_instance_url, $cdn_public_url)
    {
        $this->wordpress_instance_url = $wordpress_instance_url;
        $this->cdn_public_url = $cdn_public_url;
        $this->host = parse_url( $this->wordpress_instance_url)['host'];
        $this->prefix = is_ssl() ? 'https://': 'http://';
    }

    public function add_hooks()
    {
        add_filter('theme_root_uri', array($this, 'rewrite'));
        add_filter('plugins_url', array($this, 'rewrite'));
        add_filter('script_loader_src', array($this, 'rewrite'));
        add_filter('style_loader_src', array($this, 'rewrite'));
        add_filter('wp_get_attachment_url', array($this, 'rewrite'));
        add_filter('wp_get_attachment_image_src', array($this, 'rewrite_src'));
        add_filter('wp_calculate_image_srcset', array($this, 'rewrite_src'));
    }

    public function rewrite($source)
    {
                $parsed_url = parse_url($source);
                $rewrited_url = $this->prefix. $this->cdn_public_url . $parsed_url['path'];

                return $rewrited_url;
        
    }

    public function rewrite_src($sources){
        if ( is_array( $sources ) ) {
            foreach ( $sources as $source ) {
                $sources[ $source['value'] ][ 'url' ] = str_replace($this->host, $this->cdn_public_url, $sources[ $source['value'] ][ 'url' ]);
            }
            return $sources;
    } elseif(gettype($sources=='string')){
    
        return $this->rewrite($sources);
    }
    }
}
