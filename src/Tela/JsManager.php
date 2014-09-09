<?php namespace GM\Tela;

class JsManager implements JsManagerInterface {

    private $nonces = [ ];
    private $entry_points = [ ];
    private $handle;
    private $enabled = FALSE;
    private $script_added = FALSE;
    private $nonces_added = FALSE;

    public function enable() {
        $this->enabled = TRUE;
        add_action( $this->getHook(), [ $this, 'addScript' ] );
        add_action( $this->getHook(), [ $this, 'addInstancesData' ], PHP_INT_MAX );
    }

    public function enabled() {
        return $this->enabled;
    }

    public function addNonces( Array $nonces = [ ] ) {
        if ( ! empty( $nonces ) ) {
            $this->nonces = array_filter( array_merge( $this->nonces, $nonces ) );
        }
    }

    public function addEntryPoint( $tela_id ) {
        $entry_point = apply_filters( "tela_entrypoint_{$tela_id}", admin_url( 'admin-ajax.php' ) );
        $this->entry_points[ $tela_id ] = $entry_point;
    }

    public function getNonces() {
        return $this->nonces;
    }

    public function getEntryPoints() {
        return $this->entry_points;
    }

    public function scriptAdded() {
        return $this->script_added;
    }

    public function noncesAdded() {
        return $this->nonces_added;
    }

    public function addScript() {
        if ( $this->scriptAdded() ) {
            return FALSE;
        }
        $this->script_added = TRUE;
        $min = defined( 'WP_DEBUG' ) && WP_DEBUG ? '' : '.min';
        $file = "js/tela_ajax{$min}.js";
        $base = dirname( dirname( __FILE__ ) );
        $args = [
            $this->getHandle(),
            $this->getScriptUrl( $base, $file ),
            [ 'jquery' ],
            $this->getScriptVer( $base, $file ),
            TRUE
        ];
        call_user_func_array( 'wp_enqueue_script', $args );
        wp_localize_script( $this->getHandle(), 'TelaAjaxData', $this->getScriptData() );
    }

    public function addInstancesData() {
        if ( $this->noncesAdded() || ! $this->scriptAdded() ) {
            return FALSE;
        }
        $this->nonces_added = TRUE;
        $data = [
            'nonces'      => $this->getNonces(),
            'entrypoints' => $this->getEntryPoints()
        ];
        wp_localize_script( $this->getHandle(), "TelaAjaxWideData", $data );
    }

    public function getHook() {
        return is_admin() ? 'admin_enqueue_scripts' : 'wp_enqueue_scripts';
    }

    public function getHandle() {
        if ( is_null( $this->handle ) ) {
            $this->handle = uniqid( 'tela_ajax_js' );
        }
        return $this->handle;
    }

    public function getScriptUrl( $base, $relative ) {
        return plugins_url( $relative, $base );
    }

    public function getScriptVer( $base, $relative ) {
        $ver = NULL;
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            $ver = @filemtime( plugin_dir_path( $base ) . $relative ) ? : uniqid();
        }
        return $ver;
    }

    public function getScriptData() {
        $url_args = [
            'telaajax' => '1',
            'action'   => 'telaajax_proxy',
            'bid'      => get_current_blog_id()
        ];
        $data = [
            'url'      => add_query_arg( $url_args, admin_url( 'admin-ajax.php' ) ),
            'url_args' => http_build_query( $url_args ),
            'is_admin' => is_admin() ? '1' : '0'
        ];
        return $data;
    }

}