<?php
/*
Plugin Name: Laudus
Plugin URI: https://www.laudus.cl/descargas/wooCommerce.php
Description: Permite conectar su tienda con Laudus ERP.
Version: 2.0.0
Author: Laudus
Author URI: https://www.laudus.cl
License: GPL2
*/

/*  Copyright 2020 Laudus  (email : api@laudus.cl)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define('LAUDUS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LAUDUS_PLUGIN_PATH', plugin_dir_path(__FILE__));

$laudus_invoice_generate_url = get_site_url().'?page=laudus_invoice_generate';
$laudus_invoice_generate_url = get_site_url().'?page=laudus_invoice_generate';
$laudus_invoice_allow_statuses = ['completed'];
$laudus_latest_schema_version = '1.1.8';
$pdfGenerateErrorMessage = 'Invoice PDF not ready for this order right now. It will be available once order is delivered from Laudus ERP.';

if (isset($_GET['page']) && $_GET['page']=='laudus_invoice_generate') {
    laudusInvoiceGenerate();
}

function laudus_register_settings() {
   	register_setting( 'laudus_options_group', 'laudus_rut_company', 'laudus_callback' );
	register_setting( 'laudus_options_group', 'laudus_user_company', 'laudus_callback' );   
	register_setting( 'laudus_options_group', 'laudus_password_company', 'laudus_callback' );	
	register_setting( 'laudus_options_group', 'laudus_token_minutestoexpire', 'laudus_callback' );
	register_setting( 'laudus_options_group', 'laudus_token', 'laudus_callback' );
	register_setting( 'laudus_options_group', 'laudus_token_lastdate', 'laudus_callback' );
	register_setting( 'laudus_options_group', 'laudus_send_order', 'laudus_callback' );
	register_setting( 'laudus_options_group', 'laudus_let_resumeorder', 'laudus_callback' );
	register_setting( 'laudus_options_group', 'laudus_send_errors_to_admin', 'laudus_callback' );
	register_setting( 'laudus_options_group', 'laudus_customfieldshipment', 'laudus_callback' );
	if (isset($_GET['getProductListAjax'])) {
		getProductListAjax();
	}
	if (isset($_GET['getProductStockListAjax'])) {
		getProductStockListAjax();
	}
	if (isset($_GET['updateStockFromERPAjax'])) {
		updateStockFromERPAjax();
	}
        if (isset($_GET['getProductPriceListAjax'])) {
		getProductPriceListAjax();
	}
	if (isset($_GET['updatePriceFromERPAjax'])) {
		updatePriceFromERPAjax();
	}
        if (isset($_GET['laudusUpgradeAjax'])) {
		laudusUpgradeAjax();
	}
}

function laudus_register_options_page() {
	add_menu_page('Setup Laudus', 'Laudus ERP', 'manage_options', 'laudus_main_switch', 'laudus_initDesc_page', plugin_dir_url(__FILE__).'assets/images/Logo_Laudus_Menu_WordPress_24.png');
	add_submenu_page(
	    'laudus_main_switch',
	    'Acceso API',
	    'Acceso API',
	    'manage_options',
	    'laudus_item_setup',
	    'laudus_options_page'
	);
	add_submenu_page(
	    'laudus_main_switch',
	    'Formas de pago',
	    'Formas de pago',
	    'manage_options',
	    'laudus_item_payments',
	    'laudus_payments_page'
	);

	add_submenu_page(
	    'laudus_main_switch',
	    'Stocks',
	    'Stocks',
	    'manage_options',
	    'laudus_item_stocks',
	    'laudus_stocks_page'
	);
        
        add_submenu_page(
	    'laudus_main_switch',
	    'Precios',
	    'Precios',
	    'manage_options',
	    'laudus_item_prices',
	    'laudus_prices_page'
	);
}

function laudus_callback_($tcIns) {
}

function laudus_initDesc_page() {
    global $laudus_latest_schema_version;
    $current_version = get_option('laudus_current_schema_version', '1.1.6');
	?>
	<div>
	<?php screen_icon(); ?>
            <h2>LaudusERP wooCommerce plugin (v.<span id="laudus_latest_version"><?php echo $current_version?></span>)</h2>	
	<p>Comunique y sincronice informaci&oacute;n entre su tienda y el software Laudus ERP</p>
	<p>En el men&uacute; Acceso API podr&aacute; definir y verificar los datos de acceso a su empresa utilizando la API de Laudus ERP</p>
	<p>En el men&uacute; Sincronizar stocks podr&aacute; actualizar el stock de sus productos en la tienda con el stock de productos en Laudus ERP</p>
	</div>
	<?php
        
        if ($current_version != $laudus_latest_schema_version) {
        ?>
            <hr />
            <div id="laudus_upgrade_container">
                <h2>LaudusERP Update Alert</h2>	
                <p>We have added new features and fixes, please click below button to upgrade the plugin.</p>
                <a id="laudus_upgrade_button" onclick="laudusUpgrade();" class="button" href="#">Upgrade Now</a>
                <div id="laudus_upgrade_loader" style="display:none" href="#"><img src="<?php echo LAUDUS_PLUGIN_URL.'assets/images/loader.gif'?>" /></div>
                <div id="laudus_upgrade_success" style="display:none"  class="updated"><p>LaudusERP upgraded successfully.</p></div>
                <div id="laudus_upgrade_error" style="display:none"  class="error"><p>LaudusERP failed to upgrade.</p></div>
            </div>
            <hr />
            <script type="text/javascript">
                function laudusUpgrade() {
                    var laudus_latest_version = '<?php echo $laudus_latest_schema_version?>';
                    var ajaxUpdateUrl = location.href;
                    var timestamp = jQuery.now();
                    ajaxUpdateUrl += '&laudusUpgradeAjax=1&timestamp='+timestamp;
                    
                    jQuery("#laudus_upgrade_loader").show();
                    jQuery("#laudus_upgrade_button").hide();
                    
                    jQuery.post(ajaxUpdateUrl, function( status ) {
                        jQuery("#laudus_upgrade_loader").hide();
                        status = jQuery.trim(status);
                        if (status == 1) {
                            jQuery("#laudus_upgrade_success").show();
                            jQuery("#laudus_latest_version").html(laudus_latest_version);
                        } else {
                            jQuery("#laudus_upgrade_error").show();
                        }
                    });
                }
            </script>
        <?php
        }
}

function laudus_stockSync_page() {
    if (isset($_POST['updateStock'])) {
        $lcClass = 'updated';
        $loProcessResult = setAllStocksFromErp();
        if ($loProcessResult->status == false) {
            $lcClass = 'error';
        }
        echo '<div class="'.$lcClass.'"><p>'.$loProcessResult->statusMessage.'</p></div>';
    }

	?>
		<div>
			<?php screen_icon(); ?>
			<form method="post">
				<h3>ACTUALIZAR STOCKS</h3>
				<p>Actualiza el stock de todos los productos con el stock existente en su ERP</p>
                <input type="hidden" name="submitStoreConf" value="1" />
                <input type="hidden" name="updateStock" value="1" />
				<?php submit_button('sincronizar'); ?>
			</form>
		</div>
  	<?php
}

function laudus_payments_page() {
    if (isset($_POST['updateTerms'])) {
        $lcClass = 'error';
        $loProcessResult = null;
        $statusMessage = 'Configuraci&oacute;n de formas de pago no establecida';
    	if (isset($_POST['consolidatedTerms'])) {
	        $lcTerms = $_POST['consolidatedTerms'];
            $lcTerms = str_replace('\\', '', $_POST['consolidatedTerms']);
    		if (update_option('laudus_terms_map', $lcTerms)) {
                $lcClass = 'updated';
                $statusMessage = 'Configuraci&oacute;n de formas de pago establecida correctamente';  
    		}
    	}
        echo '<div class="'.$lcClass.'"><p>'.$statusMessage.'</p></div>';
    }

    $storeTerms = get_active_payment_gateways();
    $realLaudusTerms = getLaudusTerms();
    $actualTerms = get_option('laudus_terms_map');

	?>
		<div>
			<?php screen_icon(); ?>
			<form method="post" onsubmit="return composeMetaTerms();">
				<h3>CONFIGURAR FORMAS DE PAGO</h3>
				<p id="dev">Establezca la correspondencia entre las formas de pago de su tienda y las establecidas en Laudus ERP</p>
                <div class="form-group" id="mainStoreTerm" style="margin-top: 30px;"></div>
                <input type="hidden" name="submitTermsConf" value="1" />
                <input type="hidden" name="updateTerms" value="1" />
                <input type="hidden" id="consolidatedTerms" name="consolidatedTerms" value="<?php echo get_option('laudus_terms_map'); ?>" />
                <input type="hidden" id="storeTerms" name="storeTerms" value="<?php echo $storeTerms; ?>" />
                <input type="hidden" id="realLaudusTerms" name="realLaudusTerms" value="<?php echo $realLaudusTerms; ?>" />
				<?php submit_button('Guardar'); ?>
			</form>
		</div>
        <script type="text/javascript">
            var lcCmbTermsLaudus = '';
            
            function makeLaudusTermsSelects(tcLaudusTerms) {
                var loJsonLaudusTerms = jQuery.parseJSON(tcLaudusTerms);
                var lcHtmlOptions = '<option value=""></option>';     
                if (loJsonLaudusTerms.length > 0) {
                    for (var lnCountTerm = 0; lnCountTerm < loJsonLaudusTerms.length;lnCountTerm++) {
                        lcHtmlOptions = lcHtmlOptions + '<option value="' + loJsonLaudusTerms[lnCountTerm].termId + '">' + loJsonLaudusTerms[lnCountTerm].name + '</option>';
                    }
                }
                lcCmbTermsLaudus = '<select>' + lcHtmlOptions + '</select>';
            }
            
            function displayStoreTerms(tcStoreTerms) {
                var loJsonStoreTerms = jQuery.parseJSON(tcStoreTerms);

                var lcHtmlThisTerm = '<div style="clear:both;margin-top: 15px;">&nbsp;</div><div class="" style="border-bottom: 1px solid #fff;clear: both;float: left;width: 100%;"><div class="" style="float: left;width: 40%;text-align: right;font-weight: bold;">Formas de pago en su tienda</div>';
                lcHtmlThisTerm = lcHtmlThisTerm + '<div class="" style="float: left;width: 20%;text-align: center;">&nbsp;</div>';
                lcHtmlThisTerm = lcHtmlThisTerm + '<div class="" style="margin-bottom: 9px;float: left;width: 40%;font-weight: bold;">Formas de pago en Laudus ERP</div></div>';
     
                if (loJsonStoreTerms.length > 0) {
                    for (var lnCountTerm = 0; lnCountTerm < loJsonStoreTerms.length;lnCountTerm++) {
                        lcHtmlThisTerm = lcHtmlThisTerm + '<div style="clear:both;margin-top: 15px;">&nbsp;</div><div class="storeTerm" style="border-bottom: 1px solid #fff;clear: both;float: left;width: 100%;"><div class="idStoreTerm" style="float: left;width: 40%;text-align: right;" idStoreTerm="' + loJsonStoreTerms[lnCountTerm].idTerm + '">' + loJsonStoreTerms[lnCountTerm].displayName + '</div>';
                        lcHtmlThisTerm = lcHtmlThisTerm + '<div class="" style="float: left;width: 20%;text-align: center;"> se corresponde con </div>';
                        lcHtmlThisTerm = lcHtmlThisTerm + '<div class="laudusTerm ' + loJsonStoreTerms[lnCountTerm].idTerm + '" style="margin-bottom: 9px;float: left;width: 40%;">' + lcCmbTermsLaudus + '</div></div>';
                    }
                    jQuery('#mainStoreTerm').html(lcHtmlThisTerm);        
                }         
            }
            
            function composeMetaTerms() {
                var loTerms = jQuery('body').find('.storeTerm');
                var lcJsonTerms = '[';
                
                var loThisPaymentType;
                if (loTerms.length > 0) {
                    for (var lnCountTerm = 0; lnCountTerm < loTerms.length;lnCountTerm++) {
                        if (lnCountTerm > 0 && lnCountTerm < loTerms.length) {
                            lcJsonTerms = lcJsonTerms + ',';
                        }
                        loThisPaymentType = jQuery(loTerms[lnCountTerm]).find('.idStoreTerm');
                        loThisLaudusPaymentType = jQuery(loTerms[lnCountTerm]).find('.laudusTerm');
                        loThisLaudusPaymentType = jQuery(loThisLaudusPaymentType).find('select');
                        if (loThisPaymentType.length == 1) {
                            lcJsonTerms = lcJsonTerms + String.fromCharCode(123) + '"idStoreTerm":"' + jQuery(loThisPaymentType[0]).attr('idStoreTerm') + '","idLaudusTerm":"' + jQuery(loThisLaudusPaymentType[0]).val() + '"' + String.fromCharCode(125);   
                        }
                         
                    }
                }
                lcJsonTerms = lcJsonTerms + ']';
                jQuery('#consolidatedTerms').val(lcJsonTerms); 
                return true;
            }
            
            function displayActualMap(tcMap) {
                var loJsonActualMap = jQuery.parseJSON(tcMap);
                var lcThisStoreIdTerm = '';
                var lcThisLaudusIdTerm = '';
                if (loJsonActualMap.length > 0) {
                    for (var lnCountTerm = 0; lnCountTerm < loJsonActualMap.length; lnCountTerm++) {
                        lcThisStoreIdTerm = loJsonActualMap[lnCountTerm].idStoreTerm;
                        lcThisLaudusIdTerm = loJsonActualMap[lnCountTerm].idLaudusTerm;
                        loCmbTerms = jQuery('body').find('.' + lcThisStoreIdTerm);
                        loCmbTerms = jQuery(loCmbTerms[0]).find('select');
                        jQuery(loCmbTerms[0]).val(lcThisLaudusIdTerm);
                    }
                
                }
            }
            
            jQuery(document).ready(function(){
                if (jQuery('#realLaudusTerms').length) {
                    makeLaudusTermsSelects(<?php echo "'".$realLaudusTerms."'";?>);
                }
                if (jQuery('#storeTerms').length) {
                    displayStoreTerms(<?php echo "'".$storeTerms."'";?>);
                }                    
                if (jQuery('#consolidatedTerms').length) {
                    displayActualMap(<?php echo "'".$actualTerms."'";?>);
                } 
            });
            
        </script>         
  	<?php
}

function laudus_stocks_page() {
	?>
		<div>
			<?php screen_icon(); ?>
				<h3>Informaci&oacute;n de Stocks</h3>
                                <p id="dev">
                                    <a id="updateAllStocksFromERP" href="javascript:void(0)" onclick="updateAllStocksFromERP()" class="btn-primary btn-default btn button" style="font-size: 16px;">PULSE AQU&Iacute; PARA SINCRONIZAR TODOS LOS STOCKS</a>
                                </p>
                <div class="form-group" id="mainStoreTerm" style="margin-top: 30px;"></div>
				<div>&nbsp;</div>
				<div id="formAddPaymentPanel" class="">
					<div class="table-responsive card-body" id="stocks_container"  style="min-height: 400px;position: relative;display: table;width: 100%;">
						<p style="text-align: center;vertical-align: middle; display: table-cell;width: 100%;">
						<img src="<?php echo LAUDUS_PLUGIN_URL.'assets/images/loader.gif'?>" />
						</p>
					</div>
				</div>
		</div>
        <script type="text/javascript">
            jQuery(document).ready(function(){
                var ajaxUrl = location.href;
				var timestamp = jQuery.now();
				ajaxUrl += '&getProductStockListAjax=1&timestamp='+timestamp;

				jQuery.get(ajaxUrl, function( data ) {
					jQuery("#stocks_container").html(data);
				});
            });
            
            function customSearchLaudus(object) 
            {
                var param = jQuery(object).attr('name');
                if (param == 'laudusFilter_product_erp_stock') {
                    var searchValue = jQuery(object).val();
                    if (searchValue == '') {
                        jQuery('td.product_erp_stock_td').parent().show();
                    } else if (searchValue == 'En Laudus') {
                        jQuery('td.product_erp_stock_td').each(function(){
                            var tdValue = jQuery(this).html();
                            tdValue = jQuery.trim(tdValue);

                            if (tdValue == 'No en Laudus') {
                                jQuery(this).parent().hide();
                            } else {
                                jQuery(this).parent().show();
                            }
                        });
                    } else if (searchValue == 'No en Laudus') {
                        jQuery('td.product_erp_stock_td').each(function(){
                            var tdValue = jQuery(this).html();
                            tdValue = jQuery.trim(tdValue);
                            if (tdValue == 'No en Laudus') {
                                jQuery(this).parent().show();
                            } else {
                                jQuery(this).parent().hide();
                            }
                        });
                    }
                } else if (param == 'laudusFilter_product_action_stock') {
                    var searchValue = jQuery(object).val();
                    if (searchValue == '') {
                        jQuery('td.product_action_stock_td').parent().show();
                    } else if (searchValue == 'Stocks coinciden') {
                        jQuery('td.product_action_stock_td').each(function(){
                            var tdValue = jQuery(this).html();
                            tdValue = jQuery.trim(tdValue);

                            if (tdValue == 'Stocks coinciden') {
                                jQuery(this).parent().show();
                            } else {
                                jQuery(this).parent().hide();
                            }
                        });
                    } else if (searchValue == 'No en Laudus') {
                        jQuery('td.product_action_stock_td').each(function(){
                            var tdValue = jQuery(this).html();
                            tdValue = jQuery.trim(tdValue);
                            if (tdValue == 'No en Laudus') {
                                jQuery(this).parent().show();
                            } else {
                                jQuery(this).parent().hide();
                            }
                        });
                    } else if (searchValue == 'Actualizar Stock desde Laudus') {
                        jQuery('td.product_action_stock_td').each(function(){
                            var tdValue = jQuery(this).html();
                            tdValue = jQuery.trim(tdValue);
                            if (tdValue == 'No en Laudus' || tdValue == 'Stocks coinciden') {
                                jQuery(this).parent().hide();
                            } else {
                                jQuery(this).parent().show();
                            }
                        });
                    }
                }
            }
			
            function updateStockFromERP(id_product, stock, variant_id) {
                    jQuery("#"+id_product+"-update-stock").html('Procesando...');

                    var ajaxUrl2 = location.href;
                    var timestamp2 = jQuery.now();
                    ajaxUrl2 += '&updateStockFromERPAjax=1&timestamp='+timestamp2;

                    jQuery.post(ajaxUrl2, {'id_product':id_product, 'stock':stock, 'variant_id':variant_id}, function( output ) {
                            jQuery("#"+id_product+"-update-stock").html('Actualizar Stock desde Laudus');
                            output = jQuery.trim(output);
                            var outputArray = output.split('##')
                            if (outputArray[0] == 'change_stock') {
                                    jQuery("#"+id_product+"-woo-stock").html(outputArray[1]);
                                    jQuery("#"+id_product+"-"+variant_id+"-woo-stock-attr").html(outputArray[2]);
                                    jQuery("#"+id_product+"-update-stock").addClass('disabled');
                                    jQuery("#"+id_product+"-update-stock").parent().html('Stocks coinciden');
                                    alert('Stock Actualizado');
                            } else {
                                    alert(outputArray[0]);
                            }
                    });
            }
            
            function updateAllStocksFromERP() {
                var originalText = jQuery("#updateAllStocksFromERP").html();
                var updatedCount = 0;
                var totalRecords = jQuery('.erp-stock-update-button').length;
                if (totalRecords > 0) {
                    jQuery("#updateAllStocksFromERP").addClass('disabled');

                    var progressText = 'Progreso: '+updatedCount+ ' de '+totalRecords;
                    jQuery("#updateAllStocksFromERP").html(progressText);

                    var erpStockButtonObjects = new Array();
                    var index = 0;
                    jQuery('.erp-stock-update-button').each(function(i){
                        erpStockButtonObjects[index++] = jQuery(this);
                    });

                    updateStockLoop(0, erpStockButtonObjects, originalText, updatedCount, totalRecords);

                 } else {
                    alert('Stocks ya sincronizados');
                }
            }
            function updateStockLoop(index, erpStockButtonObjects, originalText, updatedCount, totalRecords) {
                var object = erpStockButtonObjects[index];
                var id_product = parseInt(object.attr('data-product-id'));
                var variant_id = parseInt(object.attr('data-product-variant-id'));
                var stock = parseInt(object.attr('data-product-stock'));

                jQuery("#"+id_product+"-update-stock").html('Procesando ...');

                var ajaxUrl2 = location.href;
                var timestamp2 = jQuery.now();
                ajaxUrl2 += '&updateStockFromERPAjax=1&timestamp='+timestamp2;

                jQuery.post(ajaxUrl2, {'id_product':id_product, 'variant_id':variant_id, 'stock':stock}, function( output ) {
                    jQuery("#"+id_product+"-update-stock").html('Actualizar Stock desde Laudus');
                    output = jQuery.trim(output);
                    var outputArray = output.split('##')
                    
                    if (outputArray[0] == 'change_stock') {
                            jQuery("#"+id_product+"-woo-stock").html(outputArray[1]);
                            jQuery("#"+id_product+"-"+variant_id+"-woo-stock-attr").html(outputArray[2]);
                            jQuery("#"+id_product+"-update-stock").addClass('disabled');
                            jQuery("#"+id_product+"-update-stock").parent().html('Stocks coinciden');
                            
                            updatedCount++;
                            object.removeClass('.erp-stock-update-button');

                            var progressText = 'Progreso: '+updatedCount+ ' de '+totalRecords;
                            jQuery("#updateAllStocksFromERP").html(progressText);

                            if (totalRecords == updatedCount) {
                                alert('Stocks Actualizados');
                                jQuery("#updateAllStocksFromERP").removeClass('disabled');
                                jQuery("#updateAllStocksFromERP").html(originalText);
                            } else {
                                updateStockLoop(++index, erpStockButtonObjects, originalText, updatedCount, totalRecords);
                            }
                    } else {
                        updatedCount++;
                        alert(outputArray[0]);
                        var progressText = 'Progreso: '+updatedCount+ ' de '+totalRecords;
                        jQuery("#updateAllStocksFromERP").html(progressText);

                        if (totalRecords == updatedCount) {
                            alert('Stocks Actualizados');
                            jQuery("#updateAllStocksFromERP").removeClass('disabled');
                            jQuery("#updateAllStocksFromERP").html(originalText);
                        } else {
                            updateStockLoop(++index, erpStockButtonObjects, originalText, updatedCount, totalRecords);
                        }
                    }

                });
            }
            
        </script>         
  	<?php
}

function laudus_prices_page() {
	?>
		<div>
			<?php screen_icon(); ?>
				<h3>Informaci&oacute;n de Precios</h3>
                                <p id="dev">
                                    <a id="updateAllPricesFromERP" href="javascript:void(0)" onclick="updateAllPricesFromERP()" class="btn-primary btn-default btn button" style="font-size: 16px;">PULSE AQU&Iacute; PARA SINCRONIZAR TODOS LOS PRECIOS</a>
                                </p>
                <div class="form-group" id="mainStoreTerm" style="margin-top: 30px;"></div>
				<div>&nbsp;</div>
				<div id="formAddPaymentPanel" class="">
					<div class="table-responsive card-body" id="prices_container"  style="min-height: 400px;position: relative;display: table;width: 100%;">
						<p style="text-align: center;vertical-align: middle; display: table-cell;width: 100%;">
						<img src="<?php echo LAUDUS_PLUGIN_URL.'assets/images/loader.gif'?>" />
						</p>
					</div>
				</div>
		</div>
        <script type="text/javascript">
            jQuery(document).ready(function(){
                var ajaxUrl = location.href;
				var timestamp = jQuery.now();
				ajaxUrl += '&getProductPriceListAjax=1&timestamp='+timestamp;

				jQuery.get(ajaxUrl, function( data ) {
					jQuery("#prices_container").html(data);
				});
            });
            
            function customSearchLaudus(object) 
            {
                var param = jQuery(object).attr('name');
                if (param == 'laudusFilter_product_erp_price') {
                    var searchValue = jQuery(object).val();
                    if (searchValue == '') {
                        jQuery('td.product_erp_price_td').parent().show();
                    } else if (searchValue == 'En Laudus') {
                        jQuery('td.product_erp_price_td').each(function(){
                            var tdValue = jQuery(this).html();
                            tdValue = jQuery.trim(tdValue);

                            if (tdValue == 'No en Laudus') {
                                jQuery(this).parent().hide();
                            } else {
                                jQuery(this).parent().show();
                            }
                        });
                    } else if (searchValue == 'No en Laudus') {
                        jQuery('td.product_erp_price_td').each(function(){
                            var tdValue = jQuery(this).html();
                            tdValue = jQuery.trim(tdValue);
                            if (tdValue == 'No en Laudus') {
                                jQuery(this).parent().show();
                            } else {
                                jQuery(this).parent().hide();
                            }
                        });
                    }
                } else if (param == 'laudusFilter_product_action_price') {
                    var searchValue = jQuery(object).val();
                    if (searchValue == '') {
                        jQuery('td.product_action_price_td').parent().show();
                    } else if (searchValue == 'Precios coinciden') {
                        jQuery('td.product_action_price_td').each(function(){
                            var tdValue = jQuery(this).html();
                            tdValue = jQuery.trim(tdValue);

                            if (tdValue == 'Precios coinciden') {
                                jQuery(this).parent().show();
                            } else {
                                jQuery(this).parent().hide();
                            }
                        });
                    } else if (searchValue == 'No en Laudus') {
                        jQuery('td.product_action_price_td').each(function(){
                            var tdValue = jQuery(this).html();
                            tdValue = jQuery.trim(tdValue);
                            if (tdValue == 'No en Laudus') {
                                jQuery(this).parent().show();
                            } else {
                                jQuery(this).parent().hide();
                            }
                        });
                    } else if (searchValue == 'Actualizar Precio desde Laudus') {
                        jQuery('td.product_action_price_td').each(function(){
                            var tdValue = jQuery(this).html();
                            tdValue = jQuery.trim(tdValue);
                            if (tdValue == 'No en Laudus' || tdValue == 'Precios coinciden') {
                                jQuery(this).parent().hide();
                            } else {
                                jQuery(this).parent().show();
                            }
                        });
                    }
                }
            }
			
            function updatePriceFromERP(id_product, price, price_notax, variant_id) {
                    jQuery("#"+id_product+"-update-price").html('Procesando ...');

                    var ajaxUrl2 = location.href;
                    var timestamp2 = jQuery.now();
                    ajaxUrl2 += '&updatePriceFromERPAjax=1&timestamp='+timestamp2;

                    jQuery.post(ajaxUrl2, {'id_product':id_product, 'price':price, 'variant_id':variant_id, 'price_notax':price_notax}, function( output ) {
                            jQuery("#"+id_product+"-update-price").html('Actualizar Precio desde Laudus');
                            output = jQuery.trim(output);
                            var outputArray = output.split('##')
                            if (outputArray[0] == 'change_price') {
                                    jQuery("#"+id_product+"-woo-price").html(outputArray[1]);
                                    jQuery("#"+id_product+"-"+variant_id+"-woo-price-attr").html(outputArray[2]);
                                    jQuery("#"+id_product+"-update-price").addClass('disabled');
                                    jQuery("#"+id_product+"-update-price").parent().html('Precios coinciden');
                                    alert('Precio Actualizado');
                            } else {
                                    alert(outputArray[0]);
                            }
                    });
            }
            
            function updateAllPricesFromERP() {
                var originalText = jQuery("#updateAllPricesFromERP").html();
                var updatedCount = 0;
                var totalRecords = jQuery('.erp-price-update-button').length;
                if (totalRecords > 0) {
                    jQuery("#updateAllPricesFromERP").addClass('disabled');

                    var progressText = 'Progreso: '+updatedCount+ ' de '+totalRecords;
                    jQuery("#updateAllPricesFromERP").html(progressText);

                    var erpPriceButtonObjects = new Array();
                    var index = 0;
                    jQuery('.erp-price-update-button').each(function(i){
                        erpPriceButtonObjects[index++] = jQuery(this);
                    });

                    updatePriceLoop(0, erpPriceButtonObjects, originalText, updatedCount, totalRecords);

                 } else {
                    alert('Precios ya sincronizados');
                }
            }

            function updatePriceLoop(index, erpPriceButtonObjects, originalText, updatedCount, totalRecords) {
                var object = erpPriceButtonObjects[index];
                var id_product = parseInt(object.attr('data-product-id'));
                var variant_id = parseInt(object.attr('data-product-variant-id'));
                var price = parseInt(object.attr('data-product-price'));
                var price_notax = parseInt(object.attr('data-product-price-no-tax'));

                jQuery("#"+id_product+"-update-price").html('Procesando...');

                var ajaxUrl2 = location.href;
                var timestamp2 = jQuery.now();
                ajaxUrl2 += '&updatePriceFromERPAjax=1&timestamp='+timestamp2;

                jQuery.post(ajaxUrl2, {'id_product':id_product, 'variant_id':variant_id, 'price':price, 'price_notax':price_notax}, function( output ) {
                    jQuery("#"+id_product+"-update-price").html('Actualizar Precio desde Laudus');
                    output = jQuery.trim(output);
                    var outputArray = output.split('##')
                    
                    if (outputArray[0] == 'change_price') {
                            jQuery("#"+id_product+"-woo-price").html(outputArray[1]);
                            jQuery("#"+id_product+"-"+variant_id+"-woo-price-attr").html(outputArray[2]);
                            jQuery("#"+id_product+"-update-price").addClass('disabled');
                            jQuery("#"+id_product+"-update-price").parent().html('Precios coinciden');
                            
                            updatedCount++;
                            object.removeClass('.erp-price-update-button');

                            var progressText = 'Progreso: '+updatedCount+ ' de '+totalRecords;
                            jQuery("#updateAllPricesFromERP").html(progressText);

                            if (totalRecords == updatedCount) {
                                alert('Precios Actualizados');
                                jQuery("#updateAllPricesFromERP").removeClass('disabled');
                                jQuery("#updateAllPricesFromERP").html(originalText);
                            } else {
                                updatePriceLoop(++index, erpPriceButtonObjects, originalText, updatedCount, totalRecords);
                            }
                    } else {
                        updatedCount++;
                        alert(outputArray[0]);
                        var progressText = 'Progreso: '+updatedCount+ ' de '+totalRecords;
                        jQuery("#updateAllPricesFromERP").html(progressText);

                        if (totalRecords == updatedCount) {
                            alert('Precios Actualizados');
                            jQuery("#updateAllPricesFromERP").removeClass('disabled');
                            jQuery("#updateAllPricesFromERP").html(originalText);
                        } else {
                            updatePriceLoop(++index, erpPriceButtonObjects, originalText, updatedCount, totalRecords);
                        }
                    }

                });
            }
            
        </script>         
  	<?php
}

function laudus_productsnotinerp_page() {
	?>
		<div>
			<?php screen_icon(); ?>
				<h3>Productos no en Laudus</h3>
				<p id="dev">A continuaci&oacute;n se muestran los productos que si est&aacute;nn en su tienda de Prestashop pero no est&aacute;n en Laudus ERP, recuerde que la b&uacute;squeda se hace seg&uacute;n la referencia de Wordpress.</p>
                <div class="form-group" id="mainStoreTerm" style="margin-top: 30px;"></div>
				<div>&nbsp;</div>
				<div id="formAddPaymentPanel" class="">
					<div class="table-responsive card-body" id="product_not_in_erp_container"  style="min-height: 400px;position: relative;display: table;width: 100%;">
						<p style="text-align: center;vertical-align: middle; display: table-cell;width: 100%;">
						<img src="<?php echo LAUDUS_PLUGIN_URL.'assets/images/loader.gif'?>" />
						</p>
					</div>
				</div>
		</div>
        <script type="text/javascript">
            jQuery(document).ready(function(){
                var ajaxUrl = location.href;
				var timestamp = jQuery.now();
				ajaxUrl += '&getProductListAjax=1&timestamp='+timestamp;

				jQuery.get(ajaxUrl, function( data ) {
					jQuery("#product_not_in_erp_container").html(data);
				});
            });
            
        </script>         
  	<?php
}

function laudus_options_page() {

    if (isset($_POST['updated'])) {
        if( $_POST['updated'] === 'true' ){
            handle_form_laudus();
        }
    }

    if (get_option('laudus_send_order') != 'NO') {$lnDesc1 = 0;} else {$lnDesc1 = 1;}
	?>
		<script type="text/javascript">

			lnDesc1 = <?php if (get_option('laudus_send_order') != 'NO'){echo '0';}else {echo '1';}?>;

			function showDesc(tnDesc1) {
				if (tnDesc1 == 0) {
					document.getElementById("letSubmitOrder").style.display = 'revert';
		            document.getElementById("llSendErrorsToAdmin").style.display = 'revert';
		            document.getElementById("customShipmentFieldContainer").style.display = 'revert';
				}
				else {
					document.getElementById("letSubmitOrder").style.display = 'none';
		            document.getElementById("llSendErrorsToAdmin").style.display = 'none';
					document.getElementById("customShipmentFieldContainer").style.display = 'none';
				}
			}
		    
            showDesc(lnDesc1);
		    
		</script>

		<div>
			<?php screen_icon(); ?>
			<form method="post">
                <input type="hidden" name="updated" value="true" />
				<?php settings_fields( 'laudus_options_group' ); ?>
				<h3>CONFIGURACI&Oacute;N DE ACCESO A LAUDUS API</h3>
				<p>Establezca los credenciales de su empresa para el acceso API a su sistema ERP</p>
				<table style="text-align: right;">
					<tr valign="top">
						<th scope="row"><label for="laudus_rut_company" style="margin-right: 15px;">Rut Empresa <?php echo wc_help_tip("El RUT de la empresa a la que se quiere acceder v&iacute;a API");?></label></th>
						<td><input type="text" id="laudus_rut_company" name="laudus_rut_company" value="<?php echo get_option('laudus_rut_company'); ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="laudus_user_company" style="margin-right: 15px;">Usuario API</label></th>
						<td><input type="text" id="laudus_user_company" name="laudus_user_company" value="<?php echo get_option('laudus_user_company'); ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="laudus_password_company" style="margin-right: 15px;">Clave</label></th>
						<td><input type="password" id="laudus_password_company" name="laudus_password_company" value="<?php echo get_option('laudus_password_company'); ?>" /></td>
					</tr>
				</table>
				<br />
	            <p>Establezca las propiedades adecuadas a su tienda de como debe comportarse la API</p>
	            <input type='hidden' class="" value='20' name="laudus_token_minutestoexpire" style=''/>
				<table style="text-align: left;">
					<tr valign="top" id="" style="">
						<th scope="row"><label style="margin-right: 15px;">Enviar pedidos v&iacute;a API LAUDUS</label></th>
						<td>
                            <input type="radio" name="llSendOrder" id="llSendOrder1" value="SI" <?php if (get_option('laudus_send_order') == 'SI') echo ' checked ';?> onclick="showDesc(0);" autocomplete="off"/>
							<label for="llSendOrder1">SI</label>
                            <input type="radio" name="llSendOrder" id="llSendOrder2" value="NO" <?php if (get_option('laudus_send_order') != 'SI') echo ' checked ';?> onclick="showDesc(1);" autocomplete="off"/>
							<label for="llSendOrder2">NO</label>							
						</td>
					</tr>
					<tr valign="top" id="letSubmitOrder" style="<?php if ($lnDesc1 == 1) {echo 'display: none;';}?>">
						<th scope="row"><label style="margin-right: 15px;padding-top: 15px;">Cancelar el pedido si es rechazado por la API</label></th>
						<td style="padding-top: 9px;">
                            <input type="radio" name="llLetResumeOrder" id="llLetResumeOrder1" value="SI" <?php if (get_option('laudus_let_resumeorder') == 'SI') echo ' checked ';?> autocomplete="off"/>
							<label for="llLetResumeOrder1">SI</label>
                            <input type="radio" name="llLetResumeOrder" id="llLetResumeOrder2" value="NO" <?php if (get_option('laudus_let_resumeorder') != 'SI') echo ' checked ';?> autocomplete="off"/>
							<label for="llLetResumeOrder2">NO</label>						
						</td>
					</tr>
					<tr valign="top" id="llSendErrorsToAdmin" style="<?php if ($lnDesc1 == 1) {echo 'display: none;';}?>">
						<th scope="row"><label style="margin-right: 15px;padding-top: 15px;">Avisar por email al administrador de los pedidos rechazados</label></th>
						<td style="padding-top: 9px;">
                            <input type="radio" name="llSendErrorsToAdmin" id="llSendErrorsToAdmin1" value="SI" <?php if (get_option('laudus_send_errors_to_admin') == 'SI') echo ' checked ';?> autocomplete="off"/>
							<label for="llSendErrorsToAdmin1">SI</label>
                            <input type="radio" name="llSendErrorsToAdmin" id="llSendErrorsToAdmin2" value="NO" <?php if (get_option('laudus_send_errors_to_admin') != 'SI') echo ' checked ';?> autocomplete="off"/>
							<label for="llSendErrorsToAdmin2">NO</label>						
						</td>
					</tr>
					<tr valign="top" id="customShipmentFieldContainer" style="<?php if ($lnDesc1 == 1) {echo 'display: none;';}?>">
						<th scope="row"><label for="laudus_customfieldshipment" style="margin-right: 15px;padding-top: 15px;">C&oacute;digo de producto Laudus para el concepto de transporte</label></th>
						<td style="padding-top: 5px;"><input type="text" id="laudus_customfieldshipment" name="laudus_customfieldshipment" value="<?php echo get_option('laudus_customfieldshipment'); ?>" /></td>
					</tr>
                                        <tr valign="top" id="" style="">
                                            <th scope="row"><label style="margin-right: 15px;">Usar factura v&iacute;a API LAUDUS</label></th>
                                            <td>
                                                <input type="radio" name="use_laudus_invoice" id="use_laudus_invoice1" value="SI" <?php if (get_option('use_laudus_invoice') == 'SI') echo ' checked ';?> autocomplete="off"/>
						<label for="use_laudus_invoice1">SI</label>
                                                <input type="radio" name="use_laudus_invoice" id="use_laudus_invoice2" value="NO" <?php if (get_option('use_laudus_invoice') != 'SI') echo ' checked ';?> autocomplete="off"/>
						<label for="use_laudus_invoice2">NO</label>							
                                            </td>
					</tr>
				</table>
                
				<br />
	            <p class="help-block">
	                Si su empresa tiene configurado un concepto personalizado en sus pedidos de ventas llamado 'wc_idPedido_' este ser&aacute; informado con la referencia del pedido wooCommerce.
	                Del mismo modo si su empresa tiene en clientes un concepto personalizao llamado 'wc_idCliente_' este ser&aacute; informado con la referencia del cliente de wooCommerce.
	            </p>
				<?php  submit_button(); ?>
			</form>
		</div>
  	<?php
}

function get_active_payment_gateways() {
    $payment_methods = array();
    $gateways        = WC()->payment_gateways->payment_gateways();
    foreach ( $gateways as $id => $gateway ) {
        if ( isset( $gateway->enabled ) && 'yes' === $gateway->enabled ) {
            $thisMethod = null;
            $thisMethod -> displayName = $gateway->title; 
            $thisMethod -> idTerm = $id;
            $payment_methods[] = $thisMethod;            
        }
    }

    return json_encode($payment_methods);
}

function getLaudusTerms() {

    $respond = '';
    $lcReturn = '[]';

	$lcToken = getTokenAPI();
    if ($lcToken == 'voidMainData') {
        return $lcReturn; 
    }    
    if (substr($lcToken, 0, 2) == '-1') {
        $lcMessage = substr($lcToken, 2);
        return $lcReturn;
    }

    $connection = curl_init('https://erp.laudus.cl/api/terms/get/list');
    curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, false);                                                                 
    curl_setopt($connection, CURLOPT_CUSTOMREQUEST, "GET");                                                                     
    curl_setopt($connection, CURLOPT_RETURNTRANSFER, true);                                                                      
    curl_setopt($connection, CURLOPT_HTTPHEADER, array(                                                                          
        'Content-Type: application/json',
        'Accept: application/json',
        'token: '.$lcToken)      
    );                                                                                                                   

    $respond = curl_exec($connection);
    if (strlen($respond) > 0) {
        $loJsonTerms = json_decode($respond);
        if (isset($loJsonTerms->{'errorMessage'})) {
            $lnErrorNumber = $loJsonTerms->{'errorNumber'};
            if ($lnErrorNumber >= 1001 || $lnErrorNumber <=1002) {
            }
			else {
			} 
        } 
        else {
            $lcReturn = $respond;
        }
    }
    
    return $lcReturn;
}

function action_woocommerce_thankyou($order_id) { 
    
    if (get_option('laudus_send_order') != 'SI') {
        return '';
    }    

	$order = wc_get_order($order_id);
    $lcStatus = $order ->get_status();
    if (strtolower($lcStatus) == 'canceled' || strtolower($lcStatus) == 'failed' || strtolower($lcStatus) == 'refunded') {
        return '';
    }

    if (wc_prices_include_tax()) {
        $llShopHasTaxes = true;
    }
    else {
        $llShopHasTaxes = false;
    }

	$objDateTime = new DateTime('NOW');
	$ldFecha = $objDateTime->format('Y-m-d H:i:s'); 
	$ldFecha = substr($ldFecha, 0, 10).'T'.substr($ldFecha, 11).'Z';
    $lnOrderId = $order->get_order_number();
    $lcCurrencyIsoCode = $order->get_currency();    
    $lnLaudusIdCustomer = 0;
    $totalDiscount = $order->get_discount_total();
    $discountTax = $order->get_discount_tax();
    $lnShipToCost = $order->get_shipping_total();
    $total = $order->get_total(); 
    $total_tax = $order->get_total_tax(); 
    if (is_admin()) {
        $customerId = $order->get_user_id();
    }
    else {
        $customerId = get_current_user_id();  
    }
    $order_key = $order->get_order_key();
    $total_paid = '' ;
    if ($order->get_date_paid() != null) {
        $total_paid = $order->get_total() ;
    }

    $billing_name = $order->get_billing_first_name().' '.$order->get_billing_last_name();
    $billing_companyName = $order->get_billing_company();
    $billing_address = $order->get_billing_address_1().' '.$order->get_billing_address_2();
    $billing_city = $order->get_billing_city();
    $billing_state = $order->get_billing_state();
    $billing_zipCode = $order->get_billing_postcode();
    $billing_country = $order->get_billing_country();
    $email = $order->get_billing_email();
    $phone = $order->get_billing_phone();
    $shipping_address = $order->get_shipping_address_1().' '.$order->get_shipping_address_2();
    $shipping_city = $order->get_shipping_city();
    $shipping_zipCode = $order->get_shipping_postcode();
    $shipping_country = $order->get_shipping_country();
    $term_method = $order->get_payment_method();
    $term_method_desc = $order->get_payment_method_title();
    $lcJsonTermsMap = get_option('laudus_terms_map');
    $loJsonTermsMap = json_decode($lcJsonTermsMap);
    $lcOrderIdTerm = $term_method;
    $lcLaudusIdTerm = '';
    if (strlen($lcJsonTermsMap) > 2) {
        foreach ($loJsonTermsMap as $oneMapItem) {
            $lcThisKey = $oneMapItem->{'idStoreTerm'};
            $lcThisValue = $oneMapItem->{'idLaudusTerm'};
            if ($lcThisKey == $lcOrderIdTerm) {
                $lcLaudusIdTerm = $lcThisValue;
            }
        }
    }
    
    if (strlen($lcLaudusIdTerm) == 0) {
        $lcLaudusIdTerm = '01';
    }     

    $notes = $order->get_customer_note();
	$items = $order->get_items();
	$lcThisSku = '';
	$lnThisPrice = 0;
	$laItems = array();
	foreach ( $items as $item => $item_data) {
	  	$product = $item_data->get_product();
	    $lcThisName =$product->get_name(); 
	    $lcThisSku = $product->get_sku();
	    $lnThisQuantity = $item_data->get_quantity();
        $thisProduct = new StdClass;
	    $thisProduct->product_reference = $lcThisSku;
        $line_total2 = $item_data['total'];
        $quantity2 = $item_data['quantity'];
        $lnThisPrice = $line_total2/$quantity2;
	    $thisProduct->product_price = $lnThisPrice;
	    $thisProduct->product_quantity = $lnThisQuantity;
	    $thisProduct->product_name = $lcThisName;
	    array_push($laItems, $thisProduct);
	}	

    $lcRUT = '';
    $lcRUT = $order->get_meta('RUT');
    $loCustomer = new StdClass;
    $loCustomer->id = $customerId;
    if (strlen($billing_name) == 0) {
        $loCustomer->name = $billing_companyName;
    }
    else {
    	$loCustomer->name = $billing_name;
    }     
    $loCustomer->address = $billing_address;
    $loCustomer->zipCode = $billing_zipCode;
    $loCustomer->city = $billing_city;
    $loCustomer->country = $billing_country;	
    if (strlen($billing_companyName) == 0) {
        $loCustomer->billingName = $billing_companyName;
    }
    else {
    	$loCustomer->billingName = $billing_name;
    }    

    $loCustomer->billingAddress = $loCustomer->address;
    $loCustomer->billingZipCode = $loCustomer->zipCode;
    $loCustomer->billingCity = $loCustomer->city;
    $loCustomer->billingCountry = $loCustomer->country;
    $loCustomer->activity = 'giro cliente ecomerce';
    $loCustomer->blocked = false;
    $loCustomer->notes = 'Cliente eCommerce wooCommerce.customerId: '.$customerId;
    $loCustomer->wc_idCliente_ = $customerId;    
    $loCustomer->phone = $phone;
    $loCustomer->email = $email;
    $loCustomer->vatId = $lcRUT;
    $loCustomer->country_iso_code2 = $loCustomer->country ;
    $lnLaudusIdCustomer = getOrCreateLaudusCustomer($lcRUT, $loCustomer);
    if (gettype($lnLaudusIdCustomer) == "string") {
        return $lnLaudusIdCustomer;   
    }

    $loOrderJson = new StdClass;
	$loOrderJson->date = $ldFecha;
    $loOrderJson->shopHasTaxes = $llShopHasTaxes;
	$loOrderJson->customerId = (int)$lnLaudusIdCustomer;
    $loOrderJson->eShop_idOrder = $lnOrderId;
    $loOrderJson->currencyISO = $lcCurrencyIsoCode;
    $loOrderJson->termId = $lcLaudusIdTerm;
    $loOrderJson->address = new StdClass;
    $loOrderJson->address->address = $shipping_address;
    $loOrderJson->address->zipCode = $shipping_zipCode;
    $loOrderJson->address->city = $shipping_city;
    $loOrderJson->address->country = $billing_country;
    $loOrderJson->date = $ldFecha;
    $loOrderJson->dueDate = $ldFecha;
    $loOrderJson->archived = false;
    $loOrderJson->locked = false;
    $loOrderJson->notes = 'Pedido eCommerce wooCommerce.id: '.$lnOrderId;
    $loOrderJson->referencia_wc_ = $lnOrderId;
    $loOrderJson->wc_idPedido_ = $lnOrderId;
    $loOrderJson->total_paid = $total_paid ;
    $loOrderJson->shipToCost = $lnShipToCost;
    $loOrderJson->shiptoNotes = $notes;
	$loOrderJson->reduction_percent = $discountTax;
	$loOrderJson->detailLines = $laItems;
	$loOrderJson->customProductShipment = get_option('laudus_customfieldshipment');

    $lcOrderJson = json_encode($loOrderJson);
	$lcToken = getTokenAPI();
    if ($lcToken == 'voidMainData') {
        return ''; 
    }    
    if (substr($lcToken, 0, 2) == '-1') {
        $lcMessage = substr($lcToken, 2);
        return '';
    }

    $connection = curl_init('https://erp.laudus.cl/api/v7/orders/new');
    curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, false);                                                                 
    curl_setopt($connection, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
    curl_setopt($connection, CURLOPT_POSTFIELDS, $lcOrderJson);                                                                  
    curl_setopt($connection, CURLOPT_RETURNTRANSFER, true);                                                                      
    curl_setopt($connection, CURLOPT_HTTPHEADER, array(                                                                          
        'Content-Type: application/json',  
		'token: '.$lcToken,                                                                              
        'Content-Length: ' . strlen($lcOrderJson))                                                                       
    );                                  
    $respond = curl_exec($connection);
    $lcError = '';
    if (strlen($respond) > 0) {
        $loJsonOrder = json_decode($respond);
        if (isset($loJsonOrder->{'orderNumber'})) {
            $lnOrderLaudusID = $loJsonOrder->{'orderNumber'};
			return '';
        } 
        else {
            if (isset($loJsonOrder->{'errorMessage'})) {
                $lcError = $loJsonOrder->{'errorMessage'};
            } 
        }
    }
    
    if (get_option('laudus_send_errors_to_admin') == 'SI') {
        $wc_email = WC()->mailer()->get_emails()['WC_Email_New_Order'];
        $wc_email->settings['subject'] = __('{site_title} - No se pudo registrar en Laudus ERP el pedido ({order_number}) - {order_date}');
        $wc_email->settings['heading'] = __('No se pudo registrar el pedido en Laudus ERP, '.$lcError); 
        $wc_email->trigger($order_id);        
    }
    
    if (get_option('laudus_let_resumeorder') == 'SI') {
        $order->update_status( 'cancelled' );
    }

    return $lcError;
}

function add_vatId_field($checkout) {
 
    echo '<div id="additional_checkout_field"><h2>' . __('Información adicional') . '</h2>';
 
    woocommerce_form_field( 'RUT', array(
        'type'          => 'text',
        'class'         => array('my-field-class form-row-wide'),
        'label'         => __('RUT'),
        'placeholder'   => __('Ej: 99999999-9'),
        ), $checkout->get_value( 'RUT' ));
 
    echo '</div>';
}

function store_vatId_field( $order_id ) {
    if ( ! empty( $_POST['RUT'] ) ) {
        update_post_meta( $order_id, 'RUT', sanitize_text_field( $_POST['RUT'] ) );
    }
}

function getTokenAPI() {

    $RUT_Company = get_option('laudus_rut_company');
    $user_company = get_option('laudus_user_company');
    $password_company = get_option('laudus_password_company'); 

    if (strlen($RUT_Company) < 3 || 
            strlen($user_company) < 1 ||
            strlen($password_company) < 1) {
        return 'voidMainData';                    
    } 

    $lcToken = ''; 
    $respond = '';
    $dataForLogin = array("user" => $user_company, "password" => $password_company, "companyVatId" => $RUT_Company);                                                                    
    $dataForLogin_json = json_encode($dataForLogin);                                                                                   
    $connection = curl_init('https://erp.laudus.cl/api/users/login');     
    curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, false);                                                                 
    curl_setopt($connection, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
    curl_setopt($connection, CURLOPT_POSTFIELDS, $dataForLogin_json);                                                                  
    curl_setopt($connection, CURLOPT_RETURNTRANSFER, true);                                                                      
    curl_setopt($connection, CURLOPT_HTTPHEADER, array(                                                                          
        'Content-Type: application/json',                                                                                
        'Content-Length: ' . strlen($dataForLogin_json))                                                                       
    );                                                                                                                   

    $respond = curl_exec($connection);
    if (strlen($respond) > 0) {
        $loJsonLogin = json_decode($respond);
        if (isset($loJsonLogin->{'token'})) {
            $lcToken = $loJsonLogin->{'token'};
            $ldNow = new DateTime('NOW');
            update_option('laudus_token', $lcToken);
            update_option('laudus_token_lastdate', $ldNow->format('c'));
        } 
        else {
            if (isset($loJsonLogin->{'errorMessage'})) {
                $lcToken = '-1'.$loJsonLogin->{'errorMessage'};
            }         
        }
    }
    return $lcToken;
}

function getOrCreateLaudusCustomer($tcVatId, $toCustomer) {

    $lnIdCustomer = 0; 
    $respond = '';
    
	$lcToken = getTokenAPI();
    if ($lcToken == 'voidMainData') {
        return $lnIdCustomer; 
    }    
    if (substr($lcToken, 0, 2) == '-1') {
        $lcMessage = substr($lcToken, 2);
        return $lnIdCustomer;
    }        
    $loCustomerProperties = new StdClass;
    $loCustomerProperties->vatId = $tcVatId;
    $loCustomerProperties->wc_idCliente_ = $toCustomer->id;
    $lcCustomerProperties = json_encode($loCustomerProperties);
    $connection = curl_init('https://erp.laudus.cl/api/customers/get/customerId/byVatId/'.$tcVatId);
    curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, false);                                                                 
    curl_setopt($connection, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
    curl_setopt($connection, CURLOPT_POSTFIELDS, $lcCustomerProperties);                                                                  
    curl_setopt($connection, CURLOPT_RETURNTRANSFER, true);                                                                      
    curl_setopt($connection, CURLOPT_HTTPHEADER, array(                                                                          
        'Content-Type: application/json',  
		'token: '.$lcToken,                                                                              
        'Content-Length: ' . strlen($lcCustomerProperties))                                                                       
    );

    $respond = curl_exec($connection);
    if (strlen($respond) > 0) {
        $respond = utf8_encode($respond);
        $loJsonId = json_decode($respond);
        if (isset($loJsonId->{'errorMessage'})) {
            $lnErrorNumber = $loJsonId->{'errorNumber'};
            if ($lnErrorNumber >= 1001 || $lnErrorNumber <=1002) {
            }
			else {
			} 
        } 
        else {
            $lnIdCustomer = $loJsonId -> {'customerId'};
        }
    }
    
    if ($lnIdCustomer == 0) {
        $loNewCustomer = $toCustomer;
		$lcToken = getTokenAPI();
        if ($lcToken == 'voidMainData') {
            return $lnIdCustomer; 
        }    
        if (substr($lcToken, 0, 2) == '-1') {
            $lcMessage = substr($lcToken, 2);
            return $lnIdCustomer;
        }   
        
        $lcCustomerJson = json_encode($loNewCustomer);
        $connection = curl_init('https://erp.laudus.cl/api/customers/new');
        curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, false);                                                                 
        curl_setopt($connection, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
        curl_setopt($connection, CURLOPT_POSTFIELDS, $lcCustomerJson);                                                                  
        curl_setopt($connection, CURLOPT_RETURNTRANSFER, true);                                                                      
        curl_setopt($connection, CURLOPT_HTTPHEADER, array(                                                                          
            'Content-Type: application/json',  
			'token: '.$lcToken,                                                                              
            'Content-Length: ' . strlen($lcCustomerJson))                                                                       
        );                                  
		                                                                                 
        $respond = curl_exec($connection);
        if (strlen($respond) > 0) {
            $respond = utf8_encode($respond);
            $loJsonCustomer = json_decode($respond);
            if (isset($loJsonCustomer->{'id'})) {
                $lnIdCustomer = $loJsonCustomer->{'id'};
            } 
            else {
                if (isset($loJsonCustomer->{'errorMessage'})) {
                    $lcError = $loJsonCustomer->{'errorMessage'};
                    $lnIdCustomer = $lcError;
                    $lnErrorNumber = $loJsonCustomer->{'errorNumber'};
                    if ($lnErrorNumber >= 1001 || $lnErrorNumber <=1002) {
                    }
    				else {
    				}
                }
            }
        }
    }
    $connection = null;
    return $lnIdCustomer;
}

function handle_form_laudus() {

	if (isset($_POST['laudus_rut_company'])) {
		update_option('laudus_rut_company', $_POST['laudus_rut_company']);
	}

	if (isset($_POST['laudus_user_company'])) {
		update_option('laudus_user_company', $_POST['laudus_user_company']);
	}

	if (isset($_POST['laudus_password_company'])) {
		update_option('laudus_password_company', $_POST['laudus_password_company']);
	}

	if (isset($_POST['laudus_token_minutestoexpire'])) {
		update_option('laudus_token_minutestoexpire', $_POST['laudus_token_minutestoexpire']);
	}

	if (isset($_POST['llSendOrder'])) {
		update_option('laudus_send_order', $_POST['llSendOrder']);
	}
        
        if (isset($_POST['use_laudus_invoice'])) {
		update_option('use_laudus_invoice', $_POST['use_laudus_invoice']);
	}

	if (isset($_POST['llLetResumeOrder'])) {
		update_option('laudus_let_resumeorder', $_POST['llLetResumeOrder']);
	}

	if (isset($_POST['llSendErrorsToAdmin'])) {
		update_option('laudus_send_errors_to_admin', $_POST['llSendErrorsToAdmin']);
	}

	if (isset($_POST['laudus_customfieldshipment'])) {
		update_option('laudus_customfieldshipment', $_POST['laudus_customfieldshipment']);
	}		

	$lcToken = getTokenAPI();
    $lcClass = 'updated';
    $statusMessage = 'Configuraci&oacute;n de m&oacute;dulo guardada y credenciales de acceso API validados correctamente';
    if ($lcToken == 'voidMainData') {
        $status = false;
        $statusMessage = 'Credenciales de acceso API incompletos';
        $lcClass = 'error';
    }    
    if (substr($lcToken, 0, 2) == '-1') {
        $lcMessage = substr($lcToken, 2);
        $status = false;
        $statusMessage = 'Credenciales de acceso API no v&aacute;lidas, alerta API: '.$lcMessage;
        $lcClass = 'error';
    }
    
    echo '<div class="'.$lcClass.'"><p>'.$statusMessage.'</p></div>';
}

function setAllStocksFromErp() {

    $lcErrors = '';
    $lnIdProducto_WC = 0;
    $lnIdProducto_attribute_WC = 0;
    $loReturn = new StdClass;
    $loReturn->status = false;
    $loReturn->statusMessage = '';
    $lcToken = getTokenAPI();
    if ($lcToken == 'voidMainData') {
        $loReturn->statusMessage = 'Guarde primero los credenciales de acceso a la API'; 
        return $loReturn;
    }    
    if (substr($lcToken, 0, 2) == '-1') {
        $lcMessage = substr($lcToken, 2);
        $loReturn->statusMessage = $lcMessage; 
        return $loReturn;            
    }

    $tcWarehouseId = '';
    $connection = curl_init('https://erp.laudus.cl/api/products/get/list/stock'.$tcWarehouseId);
    curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, false);                                                                 
    curl_setopt($connection, CURLOPT_CUSTOMREQUEST, "GET");                                                                     
    curl_setopt($connection, CURLOPT_RETURNTRANSFER, true);                                                                      
    curl_setopt($connection, CURLOPT_HTTPHEADER, array(                                                                          
        'Content-Type: application/json',
        'token: '.$lcToken)      
    );                                                                                                                   

    $respond = curl_exec($connection);
    if (strlen($respond) > 0) {
        $respond = utf8_encode($respond);
        $loJsonStocks = json_decode($respond);
        if (isset($loJsonStocks->{'errorMessage'})) {
            $lnErrorNumber = $loJsonStocks->{'errorNumber'};
            $lcErrorMessage = $loJsonStocks->{'errorMessage'};
            if ($lnErrorNumber >= 1001 || $lnErrorNumber <=1002) {
            }
            $loReturn->statusMessage = $lcErrorMessage; 
            return $loReturn;
        } 
        else {
            $lnVan = 0;
            $lnIdProduct = 0;
            foreach ($loJsonStocks as $productStock) {
                $lnVan++;
                $lcThisCode = $productStock->{'code'};
                $lnThisStock = $productStock->{'stock'};
                if (strlen($lcThisCode) > 0) {
                    $lnIdProduct = wc_get_product_id_by_sku($lcThisCode);
                    if ($lnIdProduct > 0) {
                        $product = new WC_Product($lnIdProduct);
                        $product->set_stock_quantity($lnThisStock);
                        if ($lnThisStock <= 0) {
                            $product->set_stock_status('outofstock');    
                        }
                        else {
                            $product->set_stock_status('instock');
                        }
                        $product->save();                        
                    }                    
                }  
            }
            $loReturn->status = true;
            $loReturn->statusMessage = ' Procesados '.$lnVan.' stocks de productos desde su ERP '.$lcErrors ;                
        }
    }

    return $loReturn;

}

function add_meta_boxesws()
{
    add_meta_box( 'custom_order_meta_box', __( 'Laudus ERP' ),
        'custom_metabox_content', 'shop_order', 'normal', 'default');
}

function custom_metabox_content(){
    global $laudus_invoice_generate_url, $laudus_invoice_allow_statuses;
    
    $post_id = isset($_GET['post']) ? $_GET['post'] : false;
    if(! $post_id ) return;
    
    $display_invoice_link = false;
    if (get_option('use_laudus_invoice') == 'SI') {
        $order = new WC_Order($post_id);
        if ($order && $order->get_id() > 0) {
            $status = $order->get_status();
            if (in_array($status, $laudus_invoice_allow_statuses)) {
                $display_invoice_link = true;
            }
        }
    }

    $value="sendToLaudus";
    ?>
        <p><a href="?post=<?php echo $post_id; ?>&action=edit&sendToLaudus=<?php echo $value; ?>" class="button"><?php _e('Enviar a Laudus'); ?></a></p>
        <?php if ($display_invoice_link) {?>
        <p><a target="_blank" href="<?php echo $laudus_invoice_generate_url.'&order_id='.$post_id; ?>" class="button"><?php _e('View Invoice'); ?></a></p>
        <?php } ?>
    <?php
     if ( isset( $_GET['sendToLaudus'] ) && ! empty( $_GET['sendToLaudus'] ) ) {
        $lcError = action_woocommerce_thankyou($post_id);
        if (strlen($lcError) == 0) {
            echo '<p>Pedido '.$post_id.' ha sido enviado a Laudus ERP correctamente</p>';    
        }
        else {
            echo '<p>Pedido '.$post_id.' no ha sido enviado a Laudus ERP, motivo: '.$lcError.'</p>';
        }
    }
}
function filter_laudus_allow_neworder_email( $allows ) {
	return true;
}
 
function updateStockFromERPAjax() {
    try {
        if (isset($_POST['variant_id']) && $_POST['variant_id'] > 0) {
            $lnIdProduct = (int)($_POST['id_product']);
            if ($lnIdProduct < 1) {
                throw new \Exception('Invalid Prdoduct ID');
            }

            if (!is_numeric($_POST['stock'])) {
                throw new \Exception('Invalid Stock');
            }
            $variantIdProduct = (int)($_POST['variant_id']);
            $lnThisStock = trim($_POST['stock']);
            
            $product = new WC_Product($lnIdProduct);
            
            $productStock = $product->get_stock_quantity();
            $currentVariantStock = get_post_meta($variantIdProduct, '_stock', true);
            if ($currentVariantStock > 0) {
             }
            if ($lnThisStock > 0) {
             }
            
            update_post_meta($variantIdProduct, '_stock', $lnThisStock);
            if ($lnThisStock <= 0) {
                update_post_meta($variantIdProduct, '_stock_status', 'outofstock');
            }
            else {
                update_post_meta($variantIdProduct, '_stock_status', 'instock');
            }
            
            echo 'change_stock##'.$productStock.'##'.$lnThisStock;
        } else {
            $lnIdProduct = (int)($_POST['id_product']);
            if ($lnIdProduct < 1) {
                throw new \Exception('Invalid Prdoduct ID');
            }

            if (!is_numeric($_POST['stock'])) {
                throw new \Exception('Invalid Stock');
            }

            $lnThisStock = trim($_POST['stock']);
            $product = new WC_Product($lnIdProduct);
            $product->set_stock_quantity($lnThisStock);
            if ($lnThisStock <= 0) {
                $product->set_stock_status('outofstock');    
            }
            else {
                $product->set_stock_status('instock');
            }
            $product->save();
            echo 'change_stock##'.$lnThisStock.'##--';
        }
    } catch (\Exception $e) {
        echo $e->getMessage().'##';
    }
    exit;
}

function updatePriceFromERPAjax() {
    try {
        if (isset($_POST['variant_id']) && $_POST['variant_id'] > 0) {
            $lnIdProduct = (int)($_POST['id_product']);
            if ($lnIdProduct < 1) {
                throw new \Exception('PrdoductID no válido');
            }

            if (!is_numeric($_POST['price'])) {
                throw new \Exception('Precio inválido');
            }
            $variantIdProduct = (int)($_POST['variant_id']);
            $lnThisPrice = trim($_POST['price']);
            
            update_post_meta($lnIdProduct, '_tax_class', '');
            
            $woocommerce_calc_taxes = get_option('woocommerce_calc_taxes');
            $woocommerce_prices_include_tax = get_option('woocommerce_prices_include_tax');
            $taxStatus = get_post_meta($variantIdProduct, '_tax_status', true);
            
            if ($woocommerce_calc_taxes != 'yes') {
                delete_post_meta($lnIdProduct, '_sale_price');
                delete_post_meta($variantIdProduct, '_sale_price');
                update_post_meta($variantIdProduct, '_regular_price', $lnThisPrice);
                update_post_meta($variantIdProduct, '_price', $lnThisPrice);
            } else if ($woocommerce_prices_include_tax == 'yes') {
                delete_post_meta($lnIdProduct, '_sale_price');
                delete_post_meta($variantIdProduct, '_sale_price');
                update_post_meta($variantIdProduct, '_regular_price', $lnThisPrice);
                update_post_meta($variantIdProduct, '_price', $lnThisPrice);
            } else if ($taxStatus != 'taxable') {
                delete_post_meta($lnIdProduct, '_sale_price');
                delete_post_meta($variantIdProduct, '_sale_price');
                update_post_meta($variantIdProduct, '_regular_price', $lnThisPrice);
                update_post_meta($variantIdProduct, '_price', $lnThisPrice);
            } else {
                if (!is_numeric($_POST['price_notax'])) {
                    throw new \Exception('Invalid price (tax excluded)');
                }
                $priceTaxExcluded = trim($_POST['price_notax']);
                
                if ($lnThisPrice == $priceTaxExcluded) {
                    delete_post_meta($lnIdProduct, '_sale_price');
                    delete_post_meta($variantIdProduct, '_sale_price');
                    update_post_meta($variantIdProduct, '_regular_price', $lnThisPrice);
                    update_post_meta($variantIdProduct, '_price', $lnThisPrice);
                } else {
                    delete_post_meta($lnIdProduct, '_sale_price');
                    delete_post_meta($variantIdProduct, '_sale_price');
                    update_post_meta($variantIdProduct, '_regular_price', $lnThisPrice);
                    update_post_meta($variantIdProduct, '_price', $lnThisPrice);
                }
            }

            $variationProduct = new WC_Product_Variation($variantIdProduct);
            $productPrice = wc_get_price_including_tax($variationProduct);
            
            echo 'change_price##'.$productPrice.'##'.$lnThisPrice;
        } else {
            $lnIdProduct = (int)($_POST['id_product']);
            if ($lnIdProduct < 1) {
                throw new \Exception('Invalid Prdoduct ID');
            }

            if (!is_numeric($_POST['price'])) {
                throw new \Exception('Invalid price');
            }

            $lnThisPrice = trim($_POST['price']);
            
            update_post_meta($lnIdProduct, '_tax_class', '');
            
            $woocommerce_calc_taxes = get_option('woocommerce_calc_taxes');
            $woocommerce_prices_include_tax = get_option('woocommerce_prices_include_tax');
            $taxStatus = get_post_meta($lnIdProduct, '_tax_status', true);
            
            if ($woocommerce_calc_taxes != 'yes') {
                delete_post_meta($lnIdProduct, '_sale_price');
                update_post_meta($lnIdProduct, '_regular_price', $lnThisPrice);
                update_post_meta($lnIdProduct, '_price', $lnThisPrice);
            } else if ($woocommerce_prices_include_tax == 'yes') {
                delete_post_meta($lnIdProduct, '_sale_price');
                update_post_meta($lnIdProduct, '_regular_price', $lnThisPrice);
                update_post_meta($lnIdProduct, '_price', $lnThisPrice);
            } else if ($taxStatus != 'taxable') {
                delete_post_meta($lnIdProduct, '_sale_price');
                update_post_meta($lnIdProduct, '_regular_price', $lnThisPrice);
                update_post_meta($lnIdProduct, '_price', $lnThisPrice);
            } else {
                if (!is_numeric($_POST['price_notax'])) {
                    throw new \Exception('Invalid price (tax excluded)');
                }
                $priceTaxExcluded = trim($_POST['price_notax']);
                
                if ($lnThisPrice == $priceTaxExcluded) {
                    delete_post_meta($lnIdProduct, '_sale_price');
                    update_post_meta($lnIdProduct, '_regular_price', $lnThisPrice);
                    update_post_meta($lnIdProduct, '_price', $lnThisPrice);
                } else {
                    delete_post_meta($lnIdProduct, '_sale_price');
                    update_post_meta($lnIdProduct, '_regular_price', $lnThisPrice);
                    update_post_meta($lnIdProduct, '_price', $lnThisPrice);
                }
            }

            echo 'change_price##'.$lnThisPrice.'##--';
        }
    } catch (\Exception $e) {
        echo $e->getMessage().'##';
    }
    exit;
}

function createTaxStandardRateIfNotExist($taxRate) {
    global $wpdb;
    $taxRateName = 'Standard Tax:'.$taxRate .'%';
    $taxRate = number_format($taxRate, 4);
    $sql = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_country='' AND tax_rate_state='' AND tax_rate_class='' AND tax_rate = '%s'", $taxRate );
    $results = $wpdb->get_results( $sql );
    if (!$results) {
        $sql = $wpdb->prepare( "INSERT INTO {$wpdb->prefix}woocommerce_tax_rates(tax_rate_country,tax_rate_state,tax_rate,tax_rate_name,tax_rate_priority,tax_rate_compound,tax_rate_shipping,tax_rate_order,tax_rate_class) VALUES('','','%s','%s',1,0,0,0,'')", $taxRate, $taxRateName );
        $results = $wpdb->query( $sql );
    }
}

function getProductListAjax() {

	$productsNotExistInErp = [];
	$productsExistInErp[] = -1;
	
	$productsAttrNotExistInErp = [];
	$productsAttrExistInErp[] = -1;
	
	$message = '';
	$status = false;
	
	$productCodeFromErp = [];

	$lcToken = getTokenAPI();
	if ($lcToken == 'voidMainData') {
		$message = 'No se pudo identificar en Laudus';
	} else if (substr($lcToken, 0, 2) == '-1') {
		$message = substr($lcToken, 2);
	} else {
		$connection = curl_init('https://erp.laudus.cl/api/products/get/list');
		curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, false);                                                                 
		curl_setopt($connection, CURLOPT_CUSTOMREQUEST, "GET");                                                                     
		curl_setopt($connection, CURLOPT_RETURNTRANSFER, true);                                                                      
		curl_setopt($connection, CURLOPT_HTTPHEADER, array(                                                                          
			'Content-Type: application/json',
			'Accept: application/json',
			'token: '.$lcToken)      
		);                                                                                                                   

		$respond = curl_exec($connection);
		if (strlen($respond) > 0) {
			$respond = utf8_encode($respond);
			$loProductList = json_decode($respond, true);

			if (isset($loProductList['errorMessage'])) {
				$lnErrorNumber = $loJsonTerms['errorNumber'];
				if ($lnErrorNumber >= 1001 || $lnErrorNumber <=1002) {
					cleanTokenInfo();
				} else {
					refreshLastTokenDate();
				}
				$message = $loProductList['errorMessage'];
			} 
			else {
				$status = true;
				refreshLastTokenDate();
				
				foreach($loProductList as $loProduct) {
					$code = $loProduct['code'];
					$productCodeFromErp[] = $code;
				}
				
				$productCollection = [];
				$args = array(
					'post_type'      => 'product',
					'nopaging' => true
				);
				$loop = new WP_Query( $args );
				while ( $loop->have_posts() ) : $loop->the_post();
					global $product;
					
					$productObj = array();
					
					$id_product = $product->get_ID();
					$product_name = get_the_title();
					$reference = $product->get_sku();
					
					$productObj['id_product'] = $id_product;
					$productObj['product_name'] = $product_name;
					$productObj['reference'] = $reference;
					$productObj['edit_link'] = admin_url().'post.php?post='.$id_product.'&action=edit';

					if (in_array($reference, $productCodeFromErp)) {
						if ($product->is_type( 'variable' )) {
							
							$attributes = $product->get_attributes();
							$attributesNames = array();
							foreach ($attributes as $attr_slug => $attribute)
							{ 
								$attributeLabel = $attribute->get_name();
								if ($attributeLabel == 'pa_color') {
									$attributeLabel = 'Color';
								}
								$attributesNames[$attr_slug] = $attributeLabel;
							}
							
							$available_variations = $product->get_available_variations();
							foreach ($available_variations as $available_variation) 
							{ 
								$variantName = '';
								$attribute_list = $available_variation['attributes'];
								foreach ($attribute_list as $attribute_key => $attribute_value) {
									$attribute_key = str_replace('attribute_','',$attribute_key);
									$variantName .= $attributesNames[$attribute_key].':'.ucwords($attribute_value).'; ';
								}
								$attribute_name = $variantName;
								$attribute_reference = $available_variation['sku'];
								
								if (!in_array($attribute_reference, $productCodeFromErp)) {
									$productObj['product_attribute_reference'] = $attribute_reference;
									$productObj['product_attribute_name'] = $attribute_name;
									$productCollection[] = $productObj;
								}
							}
						}
					} else {
						if ($product->is_type( 'variable' )) {
							$attributes = $product->get_attributes();
							$attributesNames = array();
							foreach ($attributes as $attr_slug => $attribute)
							{ 
								$attributeLabel = $attribute->get_name();
								if ($attributeLabel == 'pa_color') {
									$attributeLabel = 'Color';
								}
								$attributesNames[$attr_slug] = $attributeLabel;
							}
							
							$available_variations = $product->get_available_variations();
							foreach ($available_variations as $available_variation) 
							{ 
								$variantName = '';
								$attribute_list = $available_variation['attributes'];
								foreach ($attribute_list as $attribute_key => $attribute_value) {
									$attribute_key = str_replace('attribute_','',$attribute_key);
									$variantName .= $attributesNames[$attribute_key].':'.ucwords($attribute_value).'; ';
								}
								$attribute_name = $variantName;
								$attribute_reference = $available_variation['sku'];
								
								if (!in_array($attribute_reference, $productCodeFromErp)) {
									$productObj['product_attribute_reference'] = $attribute_reference;
									$productObj['product_attribute_name'] = $attribute_name;
									$productCollection[] = $productObj;
								}
							}
						} else {
							$productObj['product_attribute_reference'] = '-';
							$productObj['product_attribute_name'] = '-';
							$productCollection[] = $productObj;
						}
					}
				endwhile;
				wp_reset_query();
				echo renderListSimpleHeader($productCollection);
				exit;
			}
		} else {
		}
	}

	if (!$status) {
		?>
		<div class="error"><br/><?php echo $message?><br><br></div>
		<?php
	}
	exit;
}

function getProductStockListAjax() {

	$productsNotExistInErp = [];
	$productsExistInErp[] = -1;
	
	$productsAttrNotExistInErp = [];
	$productsAttrExistInErp[] = -1;
	
	$message = '';
	$status = false;
	
	$productCodeFromErp = [];
	$productStockFromErp = [];

	$lcToken = getTokenAPI();
	if ($lcToken == 'voidMainData') {
		$message = 'No se pudo identificar en Laudus';
	} else if (substr($lcToken, 0, 2) == '-1') {
		$message = substr($lcToken, 2);
	} else {
		$connection = curl_init('https://erp.laudus.cl/api/products/get/list/stock');
		curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, false);                                                                 
		curl_setopt($connection, CURLOPT_CUSTOMREQUEST, "GET");                                                                     
		curl_setopt($connection, CURLOPT_RETURNTRANSFER, true);                                                                      
		curl_setopt($connection, CURLOPT_HTTPHEADER, array(                                                                          
			'Content-Type: application/json',
			'Accept: application/json',
			'token: '.$lcToken)      
		);                                                                                                                   

		$respond = curl_exec($connection);

		if (strlen($respond) > 0) {
			$respond = utf8_encode($respond);
			$loProductList = json_decode($respond, true);

			if (isset($loProductList['errorMessage'])) {
				$lnErrorNumber = $loJsonTerms['errorNumber'];
				if ($lnErrorNumber >= 1001 || $lnErrorNumber <=1002) {
					cleanTokenInfo();
				} else {
					refreshLastTokenDate();
				}
				$message = $loProductList['errorMessage'];
			} 
			else {
				$status = true;
				refreshLastTokenDate();
				
				foreach($loProductList as $loProduct) {
					$code = $loProduct['code'];
					$productCodeFromErp[] = $code;
					$productStockFromErp[$code] = $loProduct['stock'];
				}

				$productCollection = [];
				$args = array(
					'post_type'      => 'product',
					'nopaging' => true
				);
				$loop = new WP_Query( $args );
				while ( $loop->have_posts() ) : $loop->the_post();
					global $product;
					
					$productObj = array();
					
					$id_product = $product->get_ID();
					$product_name = get_the_title();
					$reference = $product->get_sku();
					
					$productObj['id_product'] = $id_product;
					$productObj['product_name'] = $product_name;
					$productObj['reference'] = $reference;
					$productObj['edit_link'] = admin_url().'post.php?post='.$id_product.'&action=edit';
					
					$stock = 'No Procesable';
					$manage_stock = $product->get_manage_stock();
					if ($manage_stock) {
						$stock = $product->get_stock_quantity();
					}
					$productObj['product_woo_stock'] = $stock;

					if (in_array($reference, $productCodeFromErp)) {
						$productObj['product_erp_stock'] = $productStockFromErp[$reference];
					} else {
						$productObj['product_erp_stock'] = 'No en Laudus';
					}
                                        
                                        if ($product->is_type( 'variable' )) {
                                            
                                            $attributes = $product->get_attributes();
                                            $attributesNames = array();
                                            foreach ($attributes as $attr_slug => $attribute)
                                            { 
                                                    $attributeLabel = $attribute->get_name();
                                                    if ($attributeLabel == 'pa_color') {
                                                            $attributeLabel = 'Color';
                                                    }
                                                    $attributesNames[$attr_slug] = $attributeLabel;
                                            }

                                            $available_variations = $product->get_available_variations();
                                            foreach ($available_variations as $available_variation) 
                                            { 
                                                    $variantName = '';
                                                    $attribute_list = $available_variation['attributes'];
                                                    foreach ($attribute_list as $attribute_key => $attribute_value) {
                                                            $attribute_key = str_replace('attribute_','',$attribute_key);
                                                            $variantName .= $attributesNames[$attribute_key].':'.ucwords($attribute_value).'; ';
                                                    }
                                                    $attribute_name = $variantName;
                                                    $attribute_reference = $available_variation['sku'];
                                                    
                                                    $variation_id = $available_variation['variation_id'];
                                                    $variantPost = get_post_meta($variation_id);
                                                    
                                                    $productObj['id_product_attribute'] = $variation_id;
                                                    $productObj['product_attribute_reference'] = $attribute_reference;
                                                    $productObj['product_attribute_name'] = $attribute_name;
                                                    
                                                    $variant_stock = 'No Procesable';
                                                    $variant_manage_stock = $variantPost['_manage_stock'][0];
                                                    if ($variant_manage_stock == 'yes') {
                                                            $variant_stock = $available_variation['max_qty'];
                                                    }
                                                    $productObj['product_woo_stock_attr'] = $variant_stock;

                                                    if (in_array($attribute_reference, $productCodeFromErp)) {
                                                        $productObj['product_erp_stock'] = $productStockFromErp[$attribute_reference];
                                                    } else {
                                                        $productObj['product_erp_stock'] = 'No en Laudus';
                                                    }
                                                    
                                                    
                                                    $productCollection[] = $productObj;
                                            }
                                            
                                        } else {
                                            $productCollection[] = $productObj;
                                        }

				endwhile;
				wp_reset_query();
				echo renderListSimpleHeaderStocks($productCollection);
				exit;
			}
		} else {
		}
	}

	if (!$status) {
		?>
		<div class="error"><br/><?php echo $message?><br><br></div>
		<?php
	}
	exit;
}

function laudusRoundPrice($price) {
    return $price;
}

function laudusRoundPrice2($price) {
    return round($price, 4);
}

function getProductPriceListAjax() {

	$productsNotExistInErp = [];
	$productsExistInErp[] = -1;
	
	$productsAttrNotExistInErp = [];
	$productsAttrExistInErp[] = -1;
	
	$message = '';
	$status = false;
	
	$productCodeFromErp = [];
	$productPriceFromErp = [];
        $productPriceNoTaxFromErp = [];

	$lcToken = getTokenAPI();
	if ($lcToken == 'voidMainData') {
		$message = 'No se pudo identificar en Laudus';
	} else if (substr($lcToken, 0, 2) == '-1') {
		$message = substr($lcToken, 2);
	} else {
		$connection = curl_init('https://erp.laudus.cl/api/products/get/list');
		curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, false);                                                                 
		curl_setopt($connection, CURLOPT_CUSTOMREQUEST, "GET");                                                                     
		curl_setopt($connection, CURLOPT_RETURNTRANSFER, true);                                                                      
		curl_setopt($connection, CURLOPT_HTTPHEADER, array(                                                                          
			'Content-Type: application/json',
			'Accept: application/json',
			'token: '.$lcToken)      
		);                                                                                                                   

		$respond = curl_exec($connection);

		if (strlen($respond) > 0) {
			$respond = utf8_encode($respond);
			$loProductList = json_decode($respond, true);

			if (isset($loProductList['errorMessage'])) {
				$lnErrorNumber = $loJsonTerms['errorNumber'];
				if ($lnErrorNumber >= 1001 || $lnErrorNumber <=1002) {
					cleanTokenInfo();
				} else {
					refreshLastTokenDate();
				}
				$message = $loProductList['errorMessage'];
			} 
			else {
				$status = true;
				refreshLastTokenDate();
				
				foreach($loProductList as $loProduct) {
					$code = $loProduct['code'];
					$productCodeFromErp[] = $code;
					$productPriceFromErp[$code] = laudusRoundPrice($loProduct['unitPriceWithTaxes']);
                                        $productPriceNoTaxFromErp[$code] = laudusRoundPrice($loProduct['unitPrice']);
				}

				$productCollection = [];
				$args = array(
					'post_type'      => 'product',
					'nopaging' => true
				);
				$loop = new WP_Query( $args );
				while ( $loop->have_posts() ) : $loop->the_post();
					global $product;
					
					$productObj = array();
					
					$id_product = $product->get_ID();
					$product_name = get_the_title();
					$reference = $product->get_sku();
					
					$productObj['id_product'] = $id_product;
					$productObj['product_name'] = $product_name;
					$productObj['reference'] = $reference;
					$productObj['edit_link'] = admin_url().'post.php?post='.$id_product.'&action=edit';
                                        
                                        $regularPrice = $product->get_regular_price();
  					$price = wc_get_price_including_tax($product, array( 'qty' => 1, 'price' => $regularPrice));
					$productObj['product_woo_price'] = laudusRoundPrice($price);

					if (in_array($reference, $productCodeFromErp)) {
						$productObj['product_erp_price'] = laudusRoundPrice($productPriceFromErp[$reference]);
                                                $productObj['product_erp_price_notax'] = laudusRoundPrice($productPriceNoTaxFromErp[$reference]);
					} else {
						$productObj['product_erp_price'] = 'No en Laudus';
					}
                                        
                                        if ($product->is_type( 'variable' )) {
                                            
                                            $attributes = $product->get_attributes();
                                            $attributesNames = array();
                                            foreach ($attributes as $attr_slug => $attribute)
                                            { 
                                                    $attributeLabel = $attribute->get_name();
                                                    if ($attributeLabel == 'pa_color') {
                                                            $attributeLabel = 'Color';
                                                    }
                                                    $attributesNames[$attr_slug] = $attributeLabel;
                                            }

                                            $available_variations = $product->get_available_variations();
                                            foreach ($available_variations as $available_variation) 
                                            { 
                                                    $variantName = '';
                                                    $attribute_list = $available_variation['attributes'];
                                                    foreach ($attribute_list as $attribute_key => $attribute_value) {
                                                            $attribute_key = str_replace('attribute_','',$attribute_key);
                                                            $variantName .= $attributesNames[$attribute_key].':'.ucwords($attribute_value).'; ';
                                                    }
                                                    $attribute_name = $variantName;
                                                    $attribute_reference = $available_variation['sku'];
                                                    
                                                    $variation_id = $available_variation['variation_id'];
                                                    $variationProduct = new WC_Product_Variation($variation_id);
                                                    $variantPost = get_post_meta($variation_id);
                                                    
                                                    $productObj['id_product_attribute'] = $variation_id;
                                                    $productObj['product_attribute_reference'] = $attribute_reference;
                                                    $productObj['product_attribute_name'] = $attribute_name;
                                                    
                                                    $variant_price = 'No Procesable';
                                                    $productObj['product_woo_price_attr'] = wc_get_price_including_tax($variationProduct);

                                                    if (in_array($attribute_reference, $productCodeFromErp)) {
                                                        $productObj['product_erp_price'] = laudusRoundPrice($productPriceFromErp[$attribute_reference]);
                                                        $productObj['product_erp_price_notax'] = laudusRoundPrice($productPriceNoTaxFromErp[$attribute_reference]);
                                                    } else {
                                                        $productObj['product_erp_price'] = 'No en Laudus';
                                                    }
                                                    
                                                    $productCollection[] = $productObj;
                                            }
                                            
                                        } else {
                                            $productCollection[] = $productObj;
                                        }

				endwhile;
				wp_reset_query();
				echo renderListSimpleHeaderPrices($productCollection);
				exit;
			}
		} else {
		}
	}

	if (!$status) {
		?>
		<div class="error"><br/><?php echo $message?><br><br></div>
		<?php
	}
	exit;
}

function renderListSimpleHeader($productCollection) {
	?>
	<style>
	#table-laudus td {height:25px; border-bottom: 1px solid #eaedef;}
	#table-laudus tr:nth-child(odd) {background: #fcfdfe;}
	</style>
<div style="background: white;padding: 20px;border-radius: 6px;">
	<div class="table-responsive-row clearfix">
		<table id="table-laudus" class="table laudus" style="width:100%">
			<thead>
				<tr class="nodrag nodrop">
					<th  style="width:10%;border-bottom:1px solid #a0d0eb;text-align:left;">
						<span class="title_box">ID</span>
					</th>
					<th style="width:15%;border-bottom:1px solid #a0d0eb;text-align:left;">
						<span class="title_box">SKU</span>
					</th>
					<th style="width:30%;border-bottom:1px solid #a0d0eb;text-align:left;">
						<span class="title_box">Nombre</span>
					</th>
					<th style="width:15%;border-bottom:1px solid #a0d0eb;text-align:left;">
						<span class="title_box">Variaci&oacute;n SKU</span>
					</th>
					<th style="width:30%;border-bottom:1px solid #a0d0eb;text-align:left;">
						<span class="title_box">Nombre Variaci&oacute;n</span>
					</th>
				</tr>
			</thead>
			<tbody>
			<?php
			foreach ($productCollection as $key => $product) {
			?>
				<tr class="odd">
					<td><a href="<?php echo $product['edit_link']?>" target="_blank"><?php echo $product['id_product']?></a></td>
					<td><?php echo $product['reference']?></td>
					<td><?php echo $product['product_name']?></td>
					<td><?php echo $product['product_attribute_reference']?></td>
					<td><?php echo $product['product_attribute_name']?></td>
				</tr>
			<?php
			}
			?>
			</tbody>
		</table>
	</div>
</div>
	<?php
}

function renderListSimpleHeaderStocks($productCollection) {
	?>
	<style>
	#table-laudus td {height:25px; border-bottom: 1px solid #eaedef;}
	#table-laudus tr:nth-child(odd) {background: #fcfdfe;}
	</style>
<div style="background: white;padding: 20px;border-radius: 6px;">
	<div class="table-responsive-row clearfix">
		<table id="table-laudus" class="table laudus" style="width:100%">
			<thead>
				<tr class="nodrag nodrop">
					<th  style="width:5%;border-bottom:1px solid #a0d0eb;text-align:left;">
						<span class="title_box">ID</span>
					</th>
					<th style="width:11%;border-bottom:1px solid #a0d0eb;text-align:left;">
						<span class="title_box">SKU</span>
					</th>
					<th style="width:15%;border-bottom:1px solid #a0d0eb;text-align:left;">
						<span class="title_box">Nombre</span>
					</th>
                                        <th style="width:11%;border-bottom:1px solid #a0d0eb;text-align:left;">
						<span class="title_box">Variaci&oacute;n SKU</span>
					</th>
					<th style="width:16%;border-bottom:1px solid #a0d0eb;text-align:left;">
						<span class="title_box">Nombre Variaci&oacute;n</span>
					</th>
					<th style="width:10%;border-bottom:1px solid #a0d0eb;text-align:left;">
						<span class="title_box">Stocks</span>
					</th>
                                        <th style="width:12%;border-bottom:1px solid #a0d0eb;text-align:left;">
						<span class="title_box">Stocks de la Variaci&oacute;n</span>
					</th>
					<th style="width:12%;border-bottom:1px solid #a0d0eb;text-align:left;">
						<span class="title_box">Stocks en Laudus</span>
					</th>
					<th style="width:10%;border-bottom:1px solid #a0d0eb;text-align:center;">
						<span class="title_box">Acci&oacute;n</span>
					</th>
				</tr>
                                <tr class="nodrag nodrop">
					<th  style="width:5%;border-bottom:1px solid #a0d0eb;text-align:left;">
						<span class="title_box"></span>
					</th>
					<th style="width:11%;border-bottom:1px solid #a0d0eb;text-align:left;">
						<span class="title_box"></span>
					</th>
					<th style="width:15%;border-bottom:1px solid #a0d0eb;text-align:left;">
						<span class="title_box"></span>
					</th>
                                        <th style="width:11%;border-bottom:1px solid #a0d0eb;text-align:left;">
						<span class="title_box"></span>
					</th>
                                        <th style="width:16%;border-bottom:1px solid #a0d0eb;text-align:left;">
						<span class="title_box"></span>
					</th>
					<th style="width:10%;border-bottom:1px solid #a0d0eb;text-align:left;">
						<span class="title_box"></span>
					</th>
                                        <th style="width:12%;border-bottom:1px solid #a0d0eb;text-align:left;">
						<span class="title_box"></span>
					</th>
					<th style="width:12%;border-bottom:1px solid #a0d0eb;text-align:left;">
                                            <select style="margin-left: 0" class="filter" name="laudusFilter_product_erp_stock" onchange="customSearchLaudus(this)">
						<option value="" selected="selected">-</option>
                                                <option value="En Laudus">En Laudus</option>
						<option value="No en Laudus">No en Laudus</option>
                                            </select>
					</th>
					<th style="width:10%;border-bottom:1px solid #a0d0eb;text-align:center;">
						<select class="filtercenter" name="laudusFilter_product_action_stock" onchange="customSearchLaudus(this)">
                                                    <option value="" selected="selected">-</option>
                                                    <option value="Actualizar Stock desde Laudus">Actualizar Stock desde Laudus</option>
                                                    <option value="No en Laudus">No en Laudus</option>
                                                    <option value="Stocks coinciden">Stocks coinciden</option>
						</select>
					</th>
				</tr>
			</thead>
			<tbody>
			<?php

			foreach ($productCollection as $key => $product) {
			?>
				<tr class="odd">
					<td><a href="<?php echo $product['edit_link']?>" target="_blank"><?php echo $product['id_product']?></a></td>
					<td><?php echo $product['reference']?></td>
					<td><?php echo $product['product_name']?></td>
                                        <td>
                                            <?php 
                                            if (isset($product['product_attribute_reference'])) {
                                                echo $product['product_attribute_reference'];
                                            } else {
                                                echo '--';
                                            }
                                            ?>
                                        </td>
					<td>
                                            <?php 
                                            if (isset($product['product_attribute_name'])) {
                                                echo $product['product_attribute_name'];
                                            } else {
                                                echo '--';
                                            }
                                            ?>
                                        </td>
					<td id="<?php echo $product['id_product']?>-woo-stock"><?php echo $product['product_woo_stock']?></td>
                                        <td id="<?php echo $product['id_product']?>-<?php echo $product['id_product_attribute']?>-woo-stock-attr">
                                            <?php 
                                            if (isset($product['product_woo_stock_attr'])) {
                                                echo $product['product_woo_stock_attr'];
                                            } else {
                                                echo '--';
                                            }
                                            ?>
                                        </td>
					<td class="product_erp_stock_td"><?php echo $product['product_erp_stock']?></td>
					<td class="product_action_stock_td">
                                            <?php
                                            if ($product['product_erp_stock'] === 'No en Laudus') {
                                                echo __('No en Laudus', 'laudus');
                                            } else if ($product['product_woo_stock'] === 'No Procesable') {
                                                echo __('No Procesable', 'laudus');
                                            } else if ($product['product_woo_stock_attr'] === 'No Procesable') {
                                                echo __('No Procesable', 'laudus');
                                            } else if (isset($product['product_woo_stock_attr'])) {
                                                if ($product['product_woo_stock_attr'] == $product['product_erp_stock']) {
                                                    echo 'Stocks coinciden';
                                                } else {
                                                ?>
                                                    <a 
                                                       data-product-id="<?php echo $product['id_product']?>"
                                                       data-product-variant-id="<?php echo (isset($product['id_product_attribute']) ? $product['id_product_attribute']:'') ?>"
                                                       data-product-stock="<?php echo $product['product_erp_stock']?>"
                                                       id="<?php echo $product['id_product']?>-update-stock"
                                                       class="erp-stock-update-button btn button" 
                                                       onclick="updateStockFromERP('<?php echo $product['id_product']?>','<?php echo $product['product_erp_stock']?>','<?php echo (isset($product['id_product_attribute']) ? $product['id_product_attribute']:'') ?>')"
                                                       >
                                                       Actualizar Stock desde Laudus
                                                    </a>
                                                <?php
                                                }
                                            } else if ($product['product_woo_stock'] == $product['product_erp_stock']) {
                                                echo 'Stocks coinciden';
                                            } else {
                                            ?>
                                                <a 
                                                   data-product-id="<?php echo $product['id_product']?>"
                                                   data-product-variant-id="<?php echo (isset($product['id_product_attribute']) ? $product['id_product_attribute']:'') ?>"
                                                   data-product-stock="<?php echo $product['product_erp_stock']?>"
                                                   id="<?php echo $product['id_product']?>-update-stock"
                                                   class="erp-stock-update-button btn button" 
                                                   onclick="updateStockFromERP('<?php echo $product['id_product']?>','<?php echo $product['product_erp_stock']?>','<?php echo (isset($product['id_product_attribute']) ? $product['id_product_attribute']:'') ?>')"
                                                   >
                                                   Actualizar Stock desde Laudus
                                                </a>
                                            <?php
                                            }
                                            ?>
					</td>
				</tr>
			<?php
			}
			?>
			</tbody>
		</table>
	</div>
</div>
	<?php
}

function renderListSimpleHeaderPrices($productCollection) {
	?>
	<style>
	#table-laudus td {height:25px; border-bottom: 1px solid #eaedef;}
	#table-laudus tr:nth-child(odd) {background: #fcfdfe;}
	</style>
<div style="background: white;padding: 20px;border-radius: 6px;">
	<div class="table-responsive-row clearfix">
		<table id="table-laudus" class="table laudus" style="width:100%">
			<thead>
				<tr class="nodrag nodrop">
					<th  style="width:5%;border-bottom:1px solid #a0d0eb;text-align:left;">
						<span class="title_box">ID</span>
					</th>
					<th style="width:11%;border-bottom:1px solid #a0d0eb;text-align:left;">
						<span class="title_box">SKU</span>
					</th>
					<th style="width:15%;border-bottom:1px solid #a0d0eb;text-align:left;">
						<span class="title_box">Nombre</span>
					</th>
                                        <th style="width:11%;border-bottom:1px solid #a0d0eb;text-align:left;">
						<span class="title_box">Variaci&oacute;n SKU</span>
					</th>
					<th style="width:16%;border-bottom:1px solid #a0d0eb;text-align:left;">
						<span class="title_box">Nombre Variaci&oacute;n</span>
					</th>
					<th style="width:10%;border-bottom:1px solid #a0d0eb;text-align:left;">
						<span class="title_box">Precio</span>
					</th>
                                        <th style="width:12%;border-bottom:1px solid #a0d0eb;text-align:left;">
						<span class="title_box">Precio de la Variaci&oacute;n</span>
					</th>
					<th style="width:12%;border-bottom:1px solid #a0d0eb;text-align:left;">
						<span class="title_box">Precio en Laudus</span>
					</th>
					<th style="width:10%;border-bottom:1px solid #a0d0eb;text-align:center;">
						<span class="title_box">Acci&oacute;n</span>
					</th>
				</tr>
                                <tr class="nodrag nodrop">
					<th  style="width:5%;border-bottom:1px solid #a0d0eb;text-align:left;">
						<span class="title_box"></span>
					</th>
					<th style="width:11%;border-bottom:1px solid #a0d0eb;text-align:left;">
						<span class="title_box"></span>
					</th>
					<th style="width:15%;border-bottom:1px solid #a0d0eb;text-align:left;">
						<span class="title_box"></span>
					</th>
                                        <th style="width:11%;border-bottom:1px solid #a0d0eb;text-align:left;">
						<span class="title_box"></span>
					</th>
                                        <th style="width:16%;border-bottom:1px solid #a0d0eb;text-align:left;">
						<span class="title_box"></span>
					</th>
					<th style="width:10%;border-bottom:1px solid #a0d0eb;text-align:left;">
						<span class="title_box"></span>
					</th>
                                        <th style="width:12%;border-bottom:1px solid #a0d0eb;text-align:left;">
						<span class="title_box"></span>
					</th>
					<th style="width:12%;border-bottom:1px solid #a0d0eb;text-align:left;">
                                            <select style="margin-left: 0" class="filter" name="laudusFilter_product_erp_price" onchange="customSearchLaudus(this)">
						<option value="" selected="selected">-</option>
                                                <option value="En Laudus">En Laudus</option>
						<option value="No en Laudus">No en Laudus</option>
                                            </select>
					</th>
					<th style="width:10%;border-bottom:1px solid #a0d0eb;text-align:center;">
						<select class="filtercenter" name="laudusFilter_product_action_price" onchange="customSearchLaudus(this)">
                                                    <option value="" selected="selected">-</option>
                                                    <option value="Actualizar Precio desde Laudus">Actualizar Precio desde Laudus</option>
                                                    <option value="No en Laudus">No en Laudus</option>
                                                    <option value="Precios coinciden">Precios coinciden</option>
						</select>
					</th>
				</tr>
			</thead>
			<tbody>
			<?php

			foreach ($productCollection as $key => $product) {
			?>
				<tr class="odd">
					<td><a href="<?php echo $product['edit_link']?>" target="_blank"><?php echo $product['id_product']?></a></td>
					<td><?php echo $product['reference']?></td>
					<td><?php echo $product['product_name']?></td>
                                        <td>
                                            <?php 
                                            if (isset($product['product_attribute_reference'])) {
                                                echo $product['product_attribute_reference'];
                                            } else {
                                                echo '--';
                                            }
                                            ?>
                                        </td>
					<td>
                                            <?php 
                                            if (isset($product['product_attribute_name'])) {
                                                echo $product['product_attribute_name'];
                                            } else {
                                                echo '--';
                                            }
                                            ?>
                                        </td>
					<td id="<?php echo $product['id_product']?>-woo-price"><?php echo $product['product_woo_price']?></td>
                                        <td id="<?php echo $product['id_product']?>-<?php echo $product['id_product_attribute']?>-woo-price-attr">
                                            <?php 
                                            if (isset($product['product_woo_price_attr'])) {
                                                echo $product['product_woo_price_attr'];
                                            } else {
                                                echo '--';
                                            }
                                            ?>
                                        </td>
					<td class="product_erp_price_td"><?php echo $product['product_erp_price']?></td>
					<td class="product_action_price_td">
                                            <?php
                                            if ($product['product_erp_price'] === 'No en Laudus') {
                                                echo __('No en Laudus', 'laudus');
                                            } else if ($product['product_woo_price'] === 'No Procesable') {
                                                echo __('No Procesable', 'laudus');
                                            } else if ($product['product_woo_price_attr'] === 'No Procesable') {
                                                echo __('No Procesable', 'laudus');
                                            } else if (isset($product['product_woo_price_attr'])) {
                                                if ($product['product_woo_price_attr'] == $product['product_erp_price']) {
                                                    echo 'Precios coinciden';
                                                } else {
                                                ?>
                                                    <a 
                                                       data-product-id="<?php echo $product['id_product']?>"
                                                       data-product-variant-id="<?php echo (isset($product['id_product_attribute']) ? $product['id_product_attribute']:'') ?>"
                                                       data-product-price="<?php echo $product['product_erp_price']?>"
                                                       data-product-price-no-tax="<?php echo $product['product_erp_price_notax']?>"
                                                       id="<?php echo $product['id_product']?>-update-price"
                                                       class="erp-price-update-button btn button" 
                                                       onclick="updatePriceFromERP('<?php echo $product['id_product']?>','<?php echo $product['product_erp_price']?>','<?php echo $product['product_erp_price_notax']?>','<?php echo (isset($product['id_product_attribute']) ? $product['id_product_attribute']:'') ?>')"
                                                       >
                                                       Actualizar Precio desde Laudus
                                                    </a>
                                                <?php
                                                }
                                            } else if ($product['product_woo_price'] == $product['product_erp_price']) {
                                                echo 'Precios coinciden';
                                            } else {
                                            ?>
                                                <a 
                                                   data-product-id="<?php echo $product['id_product']?>"
                                                   data-product-variant-id="<?php echo (isset($product['id_product_attribute']) ? $product['id_product_attribute']:'') ?>"
                                                   data-product-price="<?php echo $product['product_erp_price']?>"
                                                   data-product-price-no-tax="<?php echo $product['product_erp_price_notax']?>"
                                                   id="<?php echo $product['id_product']?>-update-price"
                                                   class="erp-price-update-button btn button" 
                                                   onclick="updatePriceFromERP('<?php echo $product['id_product']?>','<?php echo $product['product_erp_price']?>','<?php echo $product['product_erp_price_notax']?>','<?php echo (isset($product['id_product_attribute']) ? $product['id_product_attribute']:'') ?>')"
                                                   >
                                                   Actualizar Precio desde Laudus
                                                </a>
                                            <?php
                                            }
                                            ?>
					</td>
				</tr>
			<?php
			}
			?>
			</tbody>
		</table>
	</div>
</div>
	<?php
}

function refreshLastTokenDate() {
	$ldNowDate = new DateTime('NOW');	
	update_option('laudus_token_lastdate', $ldNowDate->format('c'));	
}
function cleanTokenInfo() {
	update_option('laudus_token', '');
    update_option('laudus_token_lastdate', '');	
}

function laudus_admin_footer() {
	?>
	<?php if (isset($_GET['page']) && $_GET['page'] == 'wc-admin') { ?>
	<section class="woocommerce-inbox-message plain message-is-unread" id="laudus_erp_section" style="display:none">
		<div class="woocommerce-inbox-message__wrapper">
			<div class="woocommerce-inbox-message__content">
				<h3 class="woocommerce-inbox-message__title">
				<img src="<?php echo LAUDUS_PLUGIN_URL.'assets/images/logo.svg'?>" style="width:75px" />
				<span style="position: relative;top: -30px;left: 10px;">Laudus ERP</span>
				</h3>
				<div class="woocommerce-inbox-message__text"><span><strong><span style="color: #295FA9;">LAUDUS ERP</span></strong> es un Software de Gestión para PYMEs que se concentra en la facilidad de uso, herramientas de an&aacute;lisis, e integraci&oacute;n de nuevas tecnolog&iacute;as, enfocando su funcionalidad desde el punto de vista del gestor o due&ntilde;o de la empresa.</span></div>
			</div>
			<div class="woocommerce-inbox-message__actions">
				<a href="<?php echo admin_url().'admin.php?page=laudus_item_setup';?>" class="components-button is-secondary">Acceso API</a>
				<a href="<?php echo admin_url().'admin.php?page=laudus_item_payments';?>" class="components-button is-secondary">Formas de pago</a>
				<a href="<?php echo admin_url().'admin.php?page=laudus_item_stockSync';?>" class="components-button is-secondary">Sincronizar stocks</a>
				<a href="<?php echo admin_url().'admin.php?page=laudus_item_productsnotinerp';?>" class="components-button is-secondary">Productos no en Laudus</a>
				<a href="<?php echo admin_url().'admin.php?page=laudus_item_stocks';?>" class="components-button is-secondary">Stocks</a>
			</div>
		</div>
	</section>
	<?php } ?>
	<script type="text/javascript">
		jQuery(document).ready(function(){
			<?php if (isset($_GET['page']) && $_GET['page'] == 'wc-admin') { ?>
			jQuery('.woocommerce-layout__main').prepend(jQuery('#laudus_erp_section'));
			jQuery('#laudus_erp_section').show();
			<?php } ?>
		});
	</script>  
	<?php
}

function laudus_get_tax_location( $location, $tax_class = '' ) {
    if (isset($_GET['page']) && $_GET['page'] == 'laudus_item_prices') {
        $location = array(
                WC()->countries->get_base_country(),
                WC()->countries->get_base_state(),
                WC()->countries->get_base_postcode(),
                WC()->countries->get_base_city(),
        );
    }
    return $location;
}

function laudus_order_action( $actions, $order ) {
    global $laudus_invoice_generate_url, $laudus_invoice_allow_statuses;
    
    if (get_option('use_laudus_invoice') == 'SI') {
        $display_invoice_link = false;
        if ($order && $order->get_id() > 0) {
            $status = $order->get_status();
            if (in_array($status, $laudus_invoice_allow_statuses)) {
                $display_invoice_link = true;
            }
        }

        if ($display_invoice_link) {
            $actions['invoice'] = array(
                'url'  => $laudus_invoice_generate_url.'&order_id='.$order->get_id(),
                'name' => __( 'View Invoice', 'woocommerce' ),
            );
        }
    }
    return $actions;
} 

function laudus_order_detail( $order ) {
    global $laudus_invoice_generate_url, $laudus_invoice_allow_statuses;
    if (get_option('use_laudus_invoice') == 'SI') {
        $display_invoice_link = false;
        if ($order && $order->get_id() > 0) {
            $status = $order->get_status();
            if (in_array($status, $laudus_invoice_allow_statuses)) {
                $display_invoice_link = true;
            }
        }
        if ($display_invoice_link) {
            $html = '<p class="view-invoice"><a target="_blank" href="'.$laudus_invoice_generate_url.'&order_id='.$order->get_id().'" class="button invoice">View Invoice</a></p>';
        }
        echo $html;
    }
}

function laudus_admin_order_action($columns) {
    if (get_option('use_laudus_invoice') == 'SI') {
        $new_columns = array();
        foreach ($columns as $column_name => $column_info) {
            $new_columns[$column_name] = $column_info;
            if ('order_date' === $column_name) {
                $new_columns['order_invoice'] = __('View Invoice', 'my-textdomain');
            }
        }
        return $new_columns;
    } else {
        return $columns;
    }
}

function laudus_admin_add_order_invoice_column_content( $column, $post_id  ) {
    global $laudus_invoice_generate_url, $laudus_invoice_allow_statuses;
    if ( 'order_invoice' === $column ) {
        $display_invoice_link = false;
        $order = new WC_Order($post_id);
        if ($order && $order->get_id() > 0) {
            $status = $order->get_status();
            if (in_array($status, $laudus_invoice_allow_statuses)) {
                $display_invoice_link = true;
            }
        }
        if ($display_invoice_link) {
            $html = '<p class="view-invoice"><a target="_blank"  href="'.$laudus_invoice_generate_url.'&order_id='.$post_id.'" class="button invoice">View Invoice</a></p>';
        }
        echo $html;
    }
}

function laudusUpgradeAjax() {
    $current_version = get_option('laudus_current_schema_version', '1.1.6');
    if ($current_version == '1.1.6') {
        laudus_upgrade_118(); 
    }
    update_option('laudus_current_schema_version', '1.1.8');
    $status = 1;
    echo $status;
    exit;
}

function laudus_upgrade_118() {
    global $wpdb;
    $sql = 'CREATE TABLE IF NOT EXISTS '.$wpdb->prefix.'laudus_invoice_relation (
            source_order_id varchar(255),
            laudus_invoice_id varchar(255)
        );';
    $wpdb->query($sql);
}

function getLaudusInvoiceIdFromDb($order_id) {
    global $wpdb;
    return $wpdb->get_var('select laudus_invoice_id FROM '.$wpdb->prefix.'laudus_invoice_relation WHERE source_order_id="'.$order_id.'"');
}
    
function addLaudusInvoiceId($order_id, $invoice_id) {
    global $wpdb;
    $wpdb->insert($wpdb->prefix.'laudus_invoice_relation', array('source_order_id' => $order_id, 'laudus_invoice_id' => $invoice_id));
}
    
function savePdfFile($filepath, $content) {
    file_put_contents($filepath, $content);
}

function outputPdf($filename, $filepath) {
    header('Content-type: application/pdf');
    header('Content-Disposition: inline; filename="' . $filename . '"');
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: ' . filesize($filepath));
    header('Accept-Ranges: bytes');
    @readfile($filepath);
}

function laudusInvoiceGenerate() {
    $order_id = sanitize_text_field($_GET['order_id']);

    if ($order_id > 0) {
        $render = false;
        $message = '';
        $invoiceId = '';
        
        $result = preparePdf($order_id);
        if ($result['status']) {
            $render = true;
            $invoiceId = $result['invoiceId'];
        } else {
            $message = $result['message']; 
        }
        
        if ($render) {
            if (ob_get_level() && ob_get_length() > 0) {
                ob_clean();
            }
            return renderPdf($invoiceId);
        } else {
            if (empty($message)) {
                $message = $pdfGenerateErrorMessage;
            }
            echo $message;
        }
        exit;
    }
}

function getNewAPIToken() {
    /*
        Main authentication on Laudus API 
        (if we got token then we set token last date too)
    */
    $RUT_Company = get_option('laudus_rut_company');
    $user_company = get_option('laudus_user_company');
    $password_company = get_option('laudus_password_company'); 
    if (strlen($RUT_Company) < 3 || 
            strlen($user_company) < 1 ||
            strlen($password_company) < 1) {
        return 'voidMainData';                    
    } 
    //compose json in order to make post
    $lcToken = ''; 
    $respond = '';
    $dataForLogin = array("userName" => $user_company, "password" => $password_company, "companyVATId" => $RUT_Company);                                                                    
    $dataForLogin_json = json_encode($dataForLogin);                                                                                   
    //connect and set basic cUrl options in order to make post                                                                                                                         
    $connection = curl_init('https://api.laudus.cl/security/login');     
    curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, false);                                                                 
    curl_setopt($connection, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
    curl_setopt($connection, CURLOPT_POSTFIELDS, $dataForLogin_json);                                                                  
    curl_setopt($connection, CURLOPT_RETURNTRANSFER, true);                                                                      
    curl_setopt($connection, CURLOPT_HTTPHEADER, array(                                                                          
        'Content-Type: application/json',                                                                                
        'Content-Length: ' . strlen($dataForLogin_json))                                                                       
    );                                                                                                                   
    //make post
    $respond = curl_exec($connection);
    //parse respond and cath errorMessage if there is no token
    if (strlen($respond) > 0) {
        $loJsonLogin = json_decode($respond);
        if (isset($loJsonLogin->{'token'})) {
            $lcToken = $loJsonLogin->{'token'};
            $ldNow = new DateTime('NOW');
        } 
        else {
            //Error handler
            if (isset($loJsonLogin->{'errorMessage'})) {
                //Displays errorMessage
                $lcToken = '-1'.$loJsonLogin->{'errorMessage'};
            }         
        }
    }
    return $lcToken;
}

function preparePdf($order_id) {
    global $pdfGenerateErrorMessage;
    $result = [];
    $result['status'] = false;
    $result['message'] = $pdfGenerateErrorMessage;

    try {
        $sourceOrderId = $order_id;

        $invoiceId = getLaudusInvoiceIdFromDb($sourceOrderId);
        if ($invoiceId && !empty($invoiceId)) {
            $result['status'] = true;
            $result['invoiceId'] = $invoiceId;
            return $result;
        }

        $lcToken = getNewAPIToken();

        $data = [];
        $data['fields'] = ['salesInvoiceId', 'sourceOrderId'];
        $data['filterBy'][] = ['field' => 'sourceOrderId', 'operator' => '=', 'value' => $sourceOrderId];
        $lcBodyJson = json_encode($data);

        $connection = curl_init('https://api.laudus.cl/sales/invoices/list');
        curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, false);                                                                 
        curl_setopt($connection, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
        curl_setopt($connection, CURLOPT_POSTFIELDS, $lcBodyJson);                                                                  
        curl_setopt($connection, CURLOPT_RETURNTRANSFER, true);                                                                      
        curl_setopt($connection, CURLOPT_HTTPHEADER, array( 
            'Accept: application/json',
            'Content-Type: application/json',  
            'Authorization: '.$lcToken,                                                                              
            'Content-Length: ' . strlen($lcBodyJson))                                                                       
        );                    

        $respond = curl_exec($connection);
        //parse respond and catch errorMessage if there is no token
        $lcError = '';
        if (strlen($respond) > 0) {
            $loJsonInvoice = json_decode($respond, true);
            if (isset($loJsonInvoice[0]['salesInvoiceId'])) {
                $lnInvoiceLaudusID = $loJsonInvoice[0]['salesInvoiceId'];
                $result['status'] = true;
                $result['message'] = 'success';
                $result['invoiceId'] = $lnInvoiceLaudusID;
                addLaudusInvoiceId($sourceOrderId, $lnInvoiceLaudusID);
            } 
            else {
                //Error handler
                if (isset($loJsonInvoice[0]['errorMessage'])) {
                    $lcError = $loJsonInvoice[0]['errorMessage'];
                    throw new \Exception($lcError);
                } 
            }
        } else {
            throw new \Exception(curl_error($connection)); 
        }
    } catch (\Exception $ex) {
        $result['message'] = 'Error: '.$ex->getMessage();
    }

    return $result;
}

function renderPdf($invoiceId) {
    global $pdfGenerateErrorMessage;
    $filename = $invoiceId.'.pdf';
    $filepath = LAUDUS_PLUGIN_PATH . 'pdf/'.$filename;
    if (file_exists($filepath)) {
        outputPdf($filename, $filepath);
    } else {
        $result = [];
        $result['status'] = false;
        $result['message'] = $pdfGenerateErrorMessage;

        try {
            $lcToken = getNewAPIToken();

            $connection = curl_init('https://api.laudus.cl/sales/invoices/'.$invoiceId.'/pdf?numberOfCopies=1&contentDisposition=inline');
            curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, false);                                                                 
            curl_setopt($connection, CURLOPT_RETURNTRANSFER, true);                                                                      
            curl_setopt($connection, CURLOPT_HTTPHEADER, array( 
                'Accept: */*',
                'Authorization: '.$lcToken)                                                                         
            );                    
            $respond = curl_exec($connection);
            if (strlen($respond) > 0) {
                savePdfFile($filepath, $respond);
                outputPdf($filename, $filepath);
            } else {
                throw new \Exception(curl_error($connection)); 
            }

        } catch (\Exception $ex) {
            $result['message'] = 'Error: '.$ex->getMessage();
        }

        echo $result['message'];
    }
}

function laudus_javascripts() {
    wp_enqueue_script( 'laudus_script', LAUDUS_PLUGIN_URL.'assets/js/laudus.js', array( 'jquery'), '1.0', true);
}
	
add_action('add_meta_boxes', 'add_meta_boxesws');
add_action('admin_init', 'laudus_register_settings');
add_action('admin_menu', 'laudus_register_options_page');
add_action('woocommerce_thankyou', 'action_woocommerce_thankyou', 10); 
add_action('woocommerce_after_order_notes', 'add_vatId_field');
add_action('woocommerce_checkout_update_order_meta', 'store_vatId_field');
add_filter('woocommerce_new_order_email_allows_resend', 'filter_laudus_allow_neworder_email' );
add_action('admin_footer', 'laudus_admin_footer');

add_action('woocommerce_my_account_my_orders_actions', 'laudus_order_action', 10, 2 );
add_action( 'woocommerce_order_details_after_order_table', 'laudus_order_detail', 10 );
add_filter('manage_edit-shop_order_columns', 'laudus_admin_order_action', 20);
add_action( 'manage_shop_order_posts_custom_column', 'laudus_admin_add_order_invoice_column_content', 20, 2 );
add_action( 'wp_enqueue_scripts', 'laudus_javascripts' )
?>