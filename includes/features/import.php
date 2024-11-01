<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//*************************************//

if( ! class_exists( 'WOOPOSGC_CSV_Importer' ) ) {
    class WOOPOSGC_CSV_Importer {
        private static $wooposgc_csvImport_instance;

        /**
         * Get the singleton instance of our plugin
         * @return class The Instance
         * @access public
         */
        public static function getInstance() {
        
            if ( !self::$wooposgc_csvImport_instance  ) {
                self::$wooposgc_csvImport_instance = new WOOPOSGC_CSV_Importer();
                self::$wooposgc_csvImport_instance->hooks();
            }

            return self::$wooposgc_csvImport_instance;

        }

        
        /**
         * Run action and filter hooks
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         *
         */
        private function hooks() {
            if ( is_admin() ) {
                add_action ( 'get_giftcard_settings', array( $this, 'wooposgc_import_page'), 10, 2);
                add_filter ( 'woocommerce_add_section_giftcard', array( $this, 'wooposgc_pro_import_settings') );
                add_action ( 'woocommerce_admin_field_import_settings', array( $this, 'import_settings') );
            }          
        }


        public function wooposgc_import_page( $options, $current_section ){

            if( $current_section == 'import' ) {
                $options = array(

                    array( 'title'  => __( 'Gift Card CSV Importer',  'wooposgc'  ), 'type' => 'title', 'id' => 'giftcard_import_options_title' ),
                    array( 'type' => 'import_settings' ),
                    array( 'type'   => 'sectionend', 'id' => 'giftcard_import' ),
                );
            }

            return $options;
        }

        public function wooposgc_pro_import_settings( $sections ){
            $import = array( 'import' => __( 'Gift Card Importer', 'wooposgc' ) );
            return array_merge( $sections, $import );

        }



        public function import_settings() {
            $messages = array();
            $errors = array();
            if ( isset( $_REQUEST['import'] ) && isset( $_FILES['csv_import'] ) ) {


                if (empty($_FILES['csv_import']['tmp_name'])) {
                    $this->log['error'][] = 'No file uploaded, aborting.';
                    $this->print_messages();
                    return;
                }

                if (!current_user_can('publish_pages') || !current_user_can('publish_posts')) {
                    $this->log['error'][] = 'You don\'t have the permissions to publish posts and pages. Please contact the blog\'s administrator.';
                    $this->print_messages();
                    return;
                }

                require_once ( WOOPOSGC_DIR . '/admin/File_CSV_DataSource/CSV_Import.php');
                
                $time_start = microtime(true);
                $csv = new File_CSV_DataSource;
                $file = $_FILES['csv_import']['tmp_name'];
                $this->stripBOM($file);



                if (!$csv->load($file)) {
                    $this->log['error'][] = 'Failed to load file, aborting.';
                    $this->print_messages();
                    return;
                }


                // pad shorter rows with empty values
                //$csv->symmetrize();

                // WordPress sets the correct timezone for date functions somewhere
                // in the bowels of wp_insert_post(). We need strtotime() to return
                // correct time before the call to wp_insert_post().
                $tz = get_option('timezone_string');
                if ($tz && function_exists('date_default_timezone_set')) {
                    date_default_timezone_set($tz);
                }

                $skipped = 0;
                $imported = 0;
                $comments = 0;
                


                foreach ( $csv->connect() as $csv_data) {

                    $giftcardNumber             = wc_clean( $csv_data["Gift Card Number"] );
                    $giftCard['description']    = 'Admin Import';
                    $giftCard['to']             = wc_clean( $csv_data["To"] );
                    $giftCard['toEmail']        = wc_clean( $csv_data["To Email"] );
                    $giftCard['from']           = wc_clean( $csv_data["From"] );
                    $giftCard['fromEmail']      = wc_clean( $csv_data["From Email"] );
                    $giftCard['amount']         = wc_clean( $csv_data["Amount"] );
                    $giftCard['balance']        = wc_clean( $csv_data["Amount"] );
                    $giftCard['sendTheEmail']   = 1;
                    $giftCard['note']           = wc_clean( $csv_data["Note"] );
                    $giftCard['expiry_date']    = wc_clean( $csv_data["Expiration"] );
                    ?>
                    <tr>
                        <td><?php echo esc_html( $giftcardNumber ); ?></td>
                        <td><?php echo esc_html( $giftCard['to'] ); ?></td>
                        <td><?php echo esc_html( $giftCard['toEmail'] ); ?></td>
                        <td><?php echo esc_html( $giftCard['from'] ); ?></td>
                        <td><?php echo esc_html( $giftCard['fromEmail'] ); ?></td>
                        <td><?php echo esc_html( $giftCard['note'] ); ?></td>
                        <td><?php echo esc_html( $giftCard['balance'] ); ?></td>
                        <td><?php echo esc_html( $giftCard['expiry_date'] ); ?></td>
                    <?php

                    if ( get_page_by_title($giftcardNumber, 'OBJECT', 'wooposgc_giftcard')  == NULL ) {
                        $post_args = array(
                            'post_title'        => sanitize_text_field( $giftcardNumber ),
                            'post_status'       => 'publish',
                            'post_type'         => 'wooposgc_giftcard',
                            'ping_status'       => 'closed',
                            'comment_status'    => 'closed'
                        );

                        $post_id = wp_insert_post( $post_args );
                        
                        if ( $post_id ) {
                            update_post_meta( $post_id, '_wooposgc_giftcard', $giftCard );
                            
                        }
                        
                        echo '<td style="color: green;"><strong>' . __('Imported', 'wooposgc') . '</strong></td>';
                        $imported++;
                    } else {
                        echo '<td style="color: red;"><strong>' . __('Duplicate', 'wooposgc') . '</strong></td>';
                    }
                    echo '</tr>';


                    $row++;
                }
            }
            echo '<script> jQuery( document ).ready( function( $ ){ $( ".submit" ).hide( ); }); </script>';
            ?>

            <div class="wrap">
                <?php if ( is_countable( $messages ) && count( $messages ) > 0 ): ?>           
		<?php foreach ( $messages as $message ): ?>
                        <div id="message" class="updated below-title"><p><?php echo $message; ?></p></div>
                    <?php endforeach; ?>
                <?php endif; ?>
          	<?php if ( is_countable( $errors ) && count( $errors ) > 0 ): ?>      
         	<?php foreach ( $errors as $error ): ?>
                        <div id="message" class="error below-title"><p><?php echo $error; ?></p></div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php 
                $address = 'admin.php?page=wc-settings&tab=giftcard&section=import';
                if( empty($theData ) ) {
                    echo '<form enctype="multipart/form-data" method="post" action="' . admin_url( $address ) .'">';
                        settings_fields( 'giftcard-import' );
                        do_settings_fields( $address, 'import_settings' );
                        ?>
                        <input type="file" name="csv_import" />

                        <input class="button-primary" type="submit" name="import" value="Import" />
                    </form>
                    <?php 
					$csvAddress = WOOPOSGC_URL . '/assets/sample.csv';
                    ?>
                    <a href="<?php echo $csvAddress; ?>" class="button-secondary"><?php _e('Download Sample CSV', 'wooposgc'); ?></a>
                 <?php } else { ?>
                    <a href="<?php echo admin_url( $address ); ?>" class="button-secondary"><?php _e('Import Another CSV', 'wooposgc'); ?></a>
                <?php } ?>



            </div><!-- .wrap -->

            <?php

            $args = array( 'post_type' => 'wooposgc_giftcard', 'posts_per_page' => -1 );
            $myposts = get_posts( $args );

            $cards = array();

            foreach ( $myposts as $key => $post ) {
                $giftCard = get_post_meta( $post->ID, '_wooposgc_giftcard', true );
				// Extra code new
				/*
				$keys_array = array(
					'description',
					'to',
					'toEmail',
					'from',
					'fromEmail',
					'amount',
					'balance',
					'note',
					'expiry',
					'Address',
					'SendLater',
					'sendTheEmail',
				);
				foreach($keys_array as $key) {
					if(empty($giftCard[$key])) {
						$giftCard[$key] = '';	
					}
				}
				*/
				                
                $cards[] = $giftCard['description'] . ', ' . $giftCard['to'] . ', ' . $giftCard['toEmail'] . ', ' . $giftCard['from'] . ', ' . $giftCard['fromEmail'] . ', ' . $giftCard['amount'] . ', ' . $giftCard['balance'] . ', ' . $giftCard['note'] . ', ' . $giftCard['expiry'] . ', ' . $giftCard['Address'] . ', ' . $giftCard['SendLater'] . ', ' . $giftCard['sendTheEmail'] . ',';
            } ?>

            <h2>Gift Card Export</h2>
            <textarea style="width: 50%; height: 200px; margin-top: 10px;"><?php echo 'description, ' . 'to, ' . 'toEmail, ' . 'from, ' . 'fromEmail, ' . 'amount, ' . 'balance, ' . 'note, ' . 'expiry, ' . 'Address, ' . 'SendLater, ' . 'sendTheEmail, '; ?>
            <?php
                echo "\r\n";
                foreach ($cards as $key => $card) {
                    print_r ($card . "\r\n");
                }
            ?>
            </textarea>
            <?php 
        }

        /**
         * Delete BOM from UTF-8 file.
         *
         * @param string $fname
         * @return void
         */
        function stripBOM($fname) {
            $res = fopen($fname, 'rb');
            if (false !== $res) {
                $bytes = fread($res, 3);
                if ($bytes == pack('CCC', 0xef, 0xbb, 0xbf)) {
                    $this->log['notice'][] = 'Getting rid of byte order mark...';
                    fclose($res);

                    $contents = file_get_contents($fname);
                    if (false === $contents) {
                        trigger_error('Failed to get file contents.', E_USER_WARNING);
                    }
                    $contents = substr($contents, 3);
                    $success = file_put_contents($fname, $contents);
                    if (false === $success) {
                        trigger_error('Failed to put file contents.', E_USER_WARNING);
                    }
                } else {
                    fclose($res);
                }
            } else {
                $this->log['error'][] = 'Failed to open file, aborting.';
            }
        }
    }
}

//*************************************//
