<?php
/**
 * Vista del panel de administración
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php settings_errors('wp_segment_messages'); ?>

    <nav class="nav-tab-wrapper">
        <a href="?page=wp-segment-integration&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">
            <?php _e('General', 'wp-segment-integration'); ?>
        </a>
        <a href="?page=wp-segment-integration&tab=wordpress" class="nav-tab <?php echo $active_tab == 'wordpress' ? 'nav-tab-active' : ''; ?>">
            <?php _e('WordPress', 'wp-segment-integration'); ?>
        </a>
        <?php if (class_exists('WooCommerce')): ?>
        <a href="?page=wp-segment-integration&tab=woocommerce" class="nav-tab <?php echo $active_tab == 'woocommerce' ? 'nav-tab-active' : ''; ?>">
            <?php _e('WooCommerce', 'wp-segment-integration'); ?>
        </a>
        <?php endif; ?>
        <a href="?page=wp-segment-integration&tab=privacy" class="nav-tab <?php echo $active_tab == 'privacy' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Privacidad', 'wp-segment-integration'); ?>
        </a>
        <a href="?page=wp-segment-integration&tab=advanced" class="nav-tab <?php echo $active_tab == 'advanced' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Avanzado', 'wp-segment-integration'); ?>
        </a>
        <a href="?page=wp-segment-integration&tab=tools" class="nav-tab <?php echo $active_tab == 'tools' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Herramientas', 'wp-segment-integration'); ?>
        </a>
    </nav>

    <div class="tab-content">
        <?php if ($active_tab == 'general'): ?>
            <div class="tab-pane active">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('wp_segment_settings_group');
                    do_settings_sections('wp-segment-integration');
                    ?>
                    
                    <div class="wp-segment-section">
                        <h2><?php _e('Estado de la Conexión', 'wp-segment-integration'); ?></h2>
                        <div class="wp-segment-connection-status">
                            <?php if ($this->settings->is_configured()): ?>
                                <span class="status-indicator status-connected"></span>
                                <span><?php _e('Configurado', 'wp-segment-integration'); ?></span>
                                <button type="button" id="test-connection" class="button button-secondary">
                                    <?php _e('Probar Conexión', 'wp-segment-integration'); ?>
                                </button>
                            <?php else: ?>
                                <span class="status-indicator status-disconnected"></span>
                                <span><?php _e('No configurado', 'wp-segment-integration'); ?></span>
                            <?php endif; ?>
                        </div>
                        <div id="connection-result"></div>
                    </div>

                    <?php submit_button(); ?>
                </form>
            </div>

        <?php elseif ($active_tab == 'wordpress'): ?>
            <div class="tab-pane">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('wp_segment_settings_group');
                    do_settings_sections('wp-segment-integration');
                    ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Eventos de Usuario', 'wp-segment-integration'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[track_user_events]" value="1" <?php checked(1, $this->settings->get_option('track_user_events', true)); ?> />
                                        <?php _e('Trackear login y registro de usuarios', 'wp-segment-integration'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Vistas de Página', 'wp-segment-integration'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[track_page_views]" value="1" <?php checked(1, $this->settings->get_option('track_page_views', true)); ?> />
                                        <?php _e('Trackear vistas de páginas automáticamente', 'wp-segment-integration'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Envíos de Formularios', 'wp-segment-integration'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[track_form_submissions]" value="1" <?php checked(1, $this->settings->get_option('track_form_submissions', false)); ?> />
                                        <?php _e('Trackear envíos de formularios', 'wp-segment-integration'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                    </table>

                    <?php submit_button(); ?>
                </form>
            </div>

        <?php elseif ($active_tab == 'woocommerce' && class_exists('WooCommerce')): ?>
            <div class="tab-pane">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('wp_segment_settings_group');
                    ?>
                    
                    <h2><?php _e('Eventos de E-commerce', 'wp-segment-integration'); ?></h2>
                    <p><?php _e('Configurar qué eventos de WooCommerce trackear según la especificación de Segment.io', 'wp-segment-integration'); ?></p>

                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Habilitar WooCommerce', 'wp-segment-integration'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[woocommerce_enabled]" value="1" <?php checked(1, $this->settings->get_option('woocommerce_enabled', true)); ?> />
                                    <?php _e('Habilitar tracking de eventos de WooCommerce', 'wp-segment-integration'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>

                    <h3><?php _e('Eventos de Productos', 'wp-segment-integration'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Producto Visto', 'wp-segment-integration'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[track_product_viewed]" value="1" <?php checked(1, $this->settings->get_option('track_product_viewed', true)); ?> />
                                    <?php _e('Trackear cuando un usuario ve un producto', 'wp-segment-integration'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Producto Agregado', 'wp-segment-integration'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[track_product_added]" value="1" <?php checked(1, $this->settings->get_option('track_product_added', true)); ?> />
                                    <?php _e('Trackear cuando un producto es agregado al carrito', 'wp-segment-integration'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Producto Removido', 'wp-segment-integration'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[track_product_removed]" value="1" <?php checked(1, $this->settings->get_option('track_product_removed', true)); ?> />
                                    <?php _e('Trackear cuando un producto es removido del carrito', 'wp-segment-integration'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>

                    <h3><?php _e('Eventos de Carrito y Checkout', 'wp-segment-integration'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Carrito Visto', 'wp-segment-integration'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[track_cart_viewed]" value="1" <?php checked(1, $this->settings->get_option('track_cart_viewed', true)); ?> />
                                    <?php _e('Trackear cuando un usuario ve su carrito', 'wp-segment-integration'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Checkout Iniciado', 'wp-segment-integration'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[track_checkout_started]" value="1" <?php checked(1, $this->settings->get_option('track_checkout_started', true)); ?> />
                                    <?php _e('Trackear cuando un usuario inicia el checkout', 'wp-segment-integration'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Pasos de Checkout', 'wp-segment-integration'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[track_checkout_steps]" value="1" <?php checked(1, $this->settings->get_option('track_checkout_steps', true)); ?> />
                                    <?php _e('Trackear pasos individuales del checkout', 'wp-segment-integration'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>

                    <h3><?php _e('Eventos de Órdenes', 'wp-segment-integration'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Orden Completada', 'wp-segment-integration'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[track_order_completed]" value="1" <?php checked(1, $this->settings->get_option('track_order_completed', true)); ?> />
                                    <?php _e('Trackear cuando una orden es completada', 'wp-segment-integration'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Orden Actualizada', 'wp-segment-integration'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[track_order_updated]" value="1" <?php checked(1, $this->settings->get_option('track_order_updated', true)); ?> />
                                    <?php _e('Trackear cuando una orden es actualizada', 'wp-segment-integration'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Orden Reembolsada', 'wp-segment-integration'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[track_order_refunded]" value="1" <?php checked(1, $this->settings->get_option('track_order_refunded', true)); ?> />
                                    <?php _e('Trackear cuando una orden es reembolsada', 'wp-segment-integration'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Orden Cancelada', 'wp-segment-integration'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[track_order_cancelled]" value="1" <?php checked(1, $this->settings->get_option('track_order_cancelled', true)); ?> />
                                    <?php _e('Trackear cuando una orden es cancelada', 'wp-segment-integration'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>

                    <h3><?php _e('Otros Eventos', 'wp-segment-integration'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Eventos de Cupones', 'wp-segment-integration'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[track_coupon_events]" value="1" <?php checked(1, $this->settings->get_option('track_coupon_events', true)); ?> />
                                    <?php _e('Trackear aplicación y remoción de cupones', 'wp-segment-integration'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Búsquedas de Productos', 'wp-segment-integration'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[track_product_searches]" value="1" <?php checked(1, $this->settings->get_option('track_product_searches', true)); ?> />
                                    <?php _e('Trackear búsquedas de productos', 'wp-segment-integration'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Listas de Productos', 'wp-segment-integration'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[track_product_lists]" value="1" <?php checked(1, $this->settings->get_option('track_product_lists', true)); ?> />
                                    <?php _e('Trackear vistas de listas de productos y categorías', 'wp-segment-integration'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>

                    <?php submit_button(); ?>
                </form>
            </div>

        <?php elseif ($active_tab == 'privacy'): ?>
            <div class="tab-pane">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('wp_segment_settings_group');
                    ?>
                    
                    <h2><?php _e('Configuraciones de Privacidad', 'wp-segment-integration'); ?></h2>
                    <p><?php _e('Configuraciones para cumplir con regulaciones de privacidad como GDPR', 'wp-segment-integration'); ?></p>

                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Anonimizar IP', 'wp-segment-integration'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[anonymize_ip]" value="1" <?php checked(1, $this->settings->get_option('anonymize_ip', false)); ?> />
                                    <?php _e('No enviar direcciones IP a Segment.io', 'wp-segment-integration'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Respetar Do Not Track', 'wp-segment-integration'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[respect_dnt]" value="1" <?php checked(1, $this->settings->get_option('respect_dnt', true)); ?> />
                                    <?php _e('No trackear usuarios que tengan Do Not Track habilitado', 'wp-segment-integration'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Consentimiento de Cookies', 'wp-segment-integration'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[cookie_consent]" value="1" <?php checked(1, $this->settings->get_option('cookie_consent', false)); ?> />
                                    <?php _e('Solo trackear después de obtener consentimiento de cookies', 'wp-segment-integration'); ?>
                                </label>
                                <p class="description"><?php _e('Requiere integración con un plugin de consentimiento de cookies', 'wp-segment-integration'); ?></p>
                            </td>
                        </tr>
                    </table>

                    <h3><?php _e('Exclusiones', 'wp-segment-integration'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Excluir Roles de Usuario', 'wp-segment-integration'); ?></th>
                            <td>
                                <?php
                                $excluded_roles = $this->settings->get_option('exclude_user_roles', array());
                                $roles = wp_roles()->roles;
                                foreach ($roles as $role_key => $role) {
                                    $checked = in_array($role_key, $excluded_roles) ? 'checked' : '';
                                    echo '<label><input type="checkbox" name="' . WP_Segment_Settings::OPTION_NAME . '[exclude_user_roles][]" value="' . esc_attr($role_key) . '" ' . $checked . ' /> ' . esc_html($role['name']) . '</label><br>';
                                }
                                ?>
                                <p class="description"><?php _e('Los usuarios con estos roles no serán trackeados', 'wp-segment-integration'); ?></p>
                            </td>
                        </tr>
                    </table>

                    <?php submit_button(); ?>
                </form>
            </div>

        <?php elseif ($active_tab == 'advanced'): ?>
            <div class="tab-pane">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('wp_segment_settings_group');
                    ?>
                    
                    <h2><?php _e('Configuración Avanzada', 'wp-segment-integration'); ?></h2>
                    <p><?php _e('Configuraciones para usuarios avanzados', 'wp-segment-integration'); ?></p>

                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Modo Debug', 'wp-segment-integration'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[debug_mode]" value="1" <?php checked(1, $this->settings->get_option('debug_mode', false)); ?> />
                                    <?php _e('Habilitar logs de debug para troubleshooting', 'wp-segment-integration'); ?>
                                </label>
                                <p class="description"><?php _e('Los logs se guardarán en el error log de WordPress', 'wp-segment-integration'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Tracking Server-Side', 'wp-segment-integration'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[server_side_tracking]" value="1" <?php checked(1, $this->settings->get_option('server_side_tracking', false)); ?> />
                                    <?php _e('Enviar eventos también desde el servidor', 'wp-segment-integration'); ?>
                                </label>
                                <p class="description"><?php _e('Útil para eventos críticos que no deben perderse por bloqueadores de ads', 'wp-segment-integration'); ?></p>
                            </td>
                        </tr>
                    </table>

                    <?php submit_button(); ?>
                </form>
            </div>

        <?php elseif ($active_tab == 'tools'): ?>
            <div class="tab-pane">
                <h2><?php _e('Herramientas', 'wp-segment-integration'); ?></h2>
                
                <div class="wp-segment-tools">
                    <div class="tool-section">
                        <h3><?php _e('Probar Conexión', 'wp-segment-integration'); ?></h3>
                        <p><?php _e('Envía un evento de prueba a Segment.io para verificar que la configuración es correcta', 'wp-segment-integration'); ?></p>
                        <button type="button" id="test-connection" class="button button-secondary">
                            <?php _e('Probar Conexión', 'wp-segment-integration'); ?>
                        </button>
                        <div id="connection-result"></div>
                    </div>

                    <div class="tool-section">
                        <h3><?php _e('Exportar/Importar Configuraciones', 'wp-segment-integration'); ?></h3>
                        <p><?php _e('Exporta o importa las configuraciones del plugin', 'wp-segment-integration'); ?></p>
                        
                        <button type="button" id="export-settings" class="button button-secondary">
                            <?php _e('Exportar Configuraciones', 'wp-segment-integration'); ?>
                        </button>
                        
                        <div class="import-section" style="margin-top: 20px;">
                            <h4><?php _e('Importar Configuraciones', 'wp-segment-integration'); ?></h4>
                            <textarea id="import-settings-data" rows="10" cols="50" placeholder="<?php _e('Pega aquí las configuraciones JSON...', 'wp-segment-integration'); ?>"></textarea><br>
                            <button type="button" id="import-settings" class="button button-secondary">
                                <?php _e('Importar Configuraciones', 'wp-segment-integration'); ?>
                            </button>
                        </div>
                    </div>

                    <div class="tool-section">
                        <h3><?php _e('Resetear Configuraciones', 'wp-segment-integration'); ?></h3>
                        <p><?php _e('Resetea todas las configuraciones a sus valores por defecto', 'wp-segment-integration'); ?></p>
                        <form method="post">
                            <?php wp_nonce_field('wp_segment_admin_action', 'wp_segment_nonce'); ?>
                            <input type="hidden" name="action" value="reset_settings">
                            <button type="submit" class="button button-secondary" onclick="return confirm('<?php _e('¿Estás seguro de que quieres resetear todas las configuraciones?', 'wp-segment-integration'); ?>')">
                                <?php _e('Resetear Configuraciones', 'wp-segment-integration'); ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.wp-segment-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.wp-segment-connection-status {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
}

.status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
}

.status-connected {
    background-color: #46b450;
}

.status-disconnected {
    background-color: #dc3232;
}

.wp-segment-tools .tool-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.tab-content {
    margin-top: 20px;
}

#connection-result {
    margin-top: 10px;
    padding: 10px;
    border-radius: 4px;
    display: none;
}

#connection-result.success {
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

#connection-result.error {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.import-section textarea {
    width: 100%;
    max-width: 600px;
}
</style>

