<?php
/**
 * WP Advanced Search 
 *
 * A PHP framework for building advanced search forms in WordPress
 *
 * @author Sean Butze
 * @link https://github.com/growthspark/wp-advanced-search
 * @version 1.0
 * @license MIT
 */

require_once('wpas-field.php');

if (!class_exists('WP_Advanced_Search')) {
	class WP_Advanced_Search {

		// Query Data
		public $wp_query;
		public $wp_query_args = array();
		public $taxonomy_operators = array();
		public $taxonomy_formats = array();
		public $meta_keys = array();
		public $orderby_relevanssi = 'relevance';
		public $relevanssi = false;

		// Form Input
		public $selected_taxonomies = array();
		public $selected_meta_keys = array();

		function __construct($args = '') {
			if ( !empty($args) ) {
				$this->process_args($args);
			}
			$this->process_orderby();
			$this->process_form_input();
		}

		/**
		 * Parses arguments and sets variables
		 *
		 * @since 1.0
		 */
		function process_args( $args ) {
			if (isset($args['wp_query'])) 
				$this->wp_query_args = $args['wp_query'];
			if (isset($args['fields']))
				$this->fields = $args['fields'];
			if (isset($args['relevanssi']))
				$this->relevanssi = $args['relevanssi'];

			$fields = $this->fields;
			foreach ($fields as $field) {

				if (isset($field['type'])) {
					switch($field['type']) {
						case ('taxonomy') :
							if (isset($field['taxonomy'])) {
								$tax = $field['taxonomy'];
								if (isset($field['operator'])) {
									$operator = $field['operator'];
								} else {
									$operator = 'AND';
								}
								$this->taxonomy_operators[$tax] = $operator;
							}
							break;
						case('meta_key') :
							if (isset($field['meta_key'])) {
								$meta = $field['meta_key'];

								if (isset($field['compare'])) {
									$operator = $field['compare'];
								} else {
									$operator = '=';
								}

								if (isset($field['data_type'])) {
									$data_type = $field['data_type'];
								} else {
									$data_type = 'CHAR';
								}

								$this->meta_keys[$meta]['compare'] = $operator;
								$this->meta_keys[$meta]['data_type'] = $data_type;
							}
							break;		
					}
				}
			}
		}

	    /**
		 * Generates and displays the search form
		 *
		 * @since 1.0
		 */
	    function the_form() {
	    	global $post;
	    	global $wp_query;

	    	$url = get_permalink($post->ID);
	    	$fields = $this->fields;
	    	$tax_fields = array();
	    	$has_search = false;
	    	$has_submit = false;
	    	$has_orderby = false;
	    	$html = 1;

	    	if (isset($_REQUEST['filter_page'])) {
	    		$page = $_REQUEST['filter_page'];
	    	} else {
	    		$page = 1;
	    	}

			// Display the filter form
	    	echo '<form id="wp-advanced-search" name="wp-advanced-search" class="wp-advanced-search" method="GET" action="'.$url.'">';

	    		// URL fix if pretty permalinks are not enabled
		    	if ( get_option('permalink_structure') == '' ) { 
		    		echo '<input type="hidden" name="page_id" value="'.$post->ID.'">'; 
		    	}

		    	foreach ($fields as $field) {
					if (isset($field['type'])) {
						if ($field['type'] == 'taxonomy') {
							if (isset($field['taxonomy']) && !in_array($field['taxonomy'], $tax_fields)) {
								$tax_fields[] = $field['taxonomy'];
								$this->tax_field($field);
							}
						} elseif ($field['type'] == 'meta_key') {
							$this->meta_field($field);
						} elseif ($field['type'] == 'author') {
							$this->author_field($field);
						} elseif ($field['type'] == 'date') {
							$this->date_field($field);
						} elseif ($field['type'] == 'post_type') {
							$this->post_type_field($field);
						} elseif ($field['type'] == 'order') {
							$this->order_field($field);
						} elseif ($field['type'] == 'orderby') {
							$has_orderby = true;
							$this->orderby_field($field);
						} elseif ($field['type'] == 'html') {
							if (empty($field['id'])) {
								$field['id'] = $html;
								$html++;
							}
							$this->html_field($field);
						} elseif ($field['type'] == 'generic') {
							$this->generic_field($field);
						} elseif ($field['type'] == 'posts_per_page') {
							$this->posts_per_page_field($field);
						} elseif ($field['type'] == 'search' && !$has_search) {
							$this->search_field($field);
							$has_search = true;
						} elseif ($field['type'] == 'submit' && !$has_submit) {
							$this->submit_button($field);
							$has_submit = true;
						}
					}
		    	}

		    if ($this->relevanssi && !$has_orderby) {
		    	echo '<input type="hidden" name="orderby" value="'.$this->orderby_relevanssi.'">';
		    }

	    	echo '</form>';
	    }

	    /**
		 * Generates a search field
		 *
		 * @since 1.0
		 */
	    function search_field( $args ) {
	    	$defaults = array(
	    					'title' => 'Search',
	    					'format' => 'text',
	    					'value' => ''
	    				);
	    	$args = wp_parse_args($args, $defaults);
	    	$format = $args['format'];
	    	if (isset($_REQUEST['search_query'])) {
	    		$value = $_REQUEST['search_query'];
	    	} else {
	    		$value = $args['value'];
	    	}
	    	$args['values'] = $value;
	    	$field = new WPAS_Field('search_query', $args);
	    	$field->build_field();
	    }

	    /**
		 * Generates a submit button
		 *
		 * @since 1.0
		 */
	    function submit_button( $args ) {
	    	$defaults = array('value' => 'Search');
	    	$args = wp_parse_args($args, $defaults);
	    	extract($args);
	    	$args['values'] = $value;
	    	$args['format'] = 'submit';
	    	$field = new WPAS_Field('submit', $args);
	    	$field->build_field();
	    }


	    /**
		 * Generates a form field containing terms for a given taxonomy
	     *
		 * @param array $args Arguments for configuring the field.
		 * @since 1.0
		 */
	    function tax_field( $args ) {
	    	$defaults = array( 
	    					'taxonomy' => 'category',
	    					'format' => 'select',
	    					'term_format' => 'slug',
	    					'terms' => array()
	    				);
	    	$args = wp_parse_args($args, $defaults);
	    	extract(wp_parse_args($args, $defaults));

	    	$this->term_formats[$taxonomy] = $term_format;

	    	$the_tax = get_taxonomy( $taxonomy );
	    	$tax_name = $the_tax->labels->name;
	    	$tax_slug = $the_tax->name;

	    	if (!$the_tax) {
				return;
			}

	    	if (isset($args['title'])) {
	    		$title = $args['title'];
	    	} else {
	    		$title = $tax_name;
	    	}

	    	$terms_objects = array();
			$term_values = array();

			if (isset($terms) && is_array($terms) && (count($terms) < 1)) {
				$term_objects = get_terms($taxonomy, array( 'hide_empty' => 0 )); 
			} else {
				foreach ($terms as $term_identifier) {
					$term = get_term_by($term_format, $term_identifier, $taxonomy);
					if ($term) {
						$term_objects[] = $term;
					}
				}
			}
				
			foreach ($term_objects as $term) {
				switch($term_format) {
					case 'id' :
					case 'ID' :
						$term_values[$term->term_id] = $term->name;
						break;
					case 'Name' :
					case 'name' :
						$term_values[$term->name] = $term->name;
						break;
					default :
						$term_values[$term->slug] = $term->name;
						break;
				}
			}

			// Don't populate with values if this is a text or textarea field
			if (empty($values)) {
				if (!($format == 'text' || $format == 'textarea')) {
					$args['values'] = $term_values;
				}
			}

			$args['title'] = $title;

			$field = new WPAS_Field('tax_'.$tax_slug, $args);
			$field->build_field();

	    }


	    /**
		 * Generates a form field containing terms for a given meta key (custom field)
	     *
		 * @param array $args Arguments for configuring the field.
		 * @since 1.0
		 */
	    function meta_field( $args ) {

	    	$defaults = array(
	    					'title' => '',
	    					'meta_key' => '',
	    					'format' => 'select',
	    					'values' => array()
	    				);

	    	$args = wp_parse_args($args, $defaults);
	    	$meta_key = $args['meta_key'];

			$field = new WPAS_Field('meta_'.$meta_key, $args);
			$field->build_field();	    	
	    }


	     /**
		 * Generates an order field
		 *
		 * @since 1.0
		 */   	    
	    function order_field( $args ) {
    		$defaults = array(
				'title' => '',
				'format' => 'select',
				'orderby' => 'title',
				'values' => array('ASC' => 'ASC', 'DESC' => 'DESC')
			);

			$args = wp_parse_args($args, $defaults);

			$field = new WPAS_Field('order', $args);
			$field->build_field();				

	    }

	     /**
		 * Generates an orderby field
		 *
		 * @since 1.0
		 */   	    
	    function orderby_field( $args ) {
    		$defaults = array('title' => '',
							  'format' => 'select',
							  'values' => array('ID' => 'ID', 
											    'post_author' => 'Author', 
											    'post_title' => 'Title', 
											    'post_date' => 'Date', 
											    'post_modified' => 'Modified',
											    'post_parent' => 'Parent ID',
											    'rand' => 'Random',
											    'comment_count' => 'Comment Count',
											    'menu_order' => 'Menu Order')
						);

			$args = wp_parse_args($args, $defaults);

			$field = new WPAS_Field('orderby', $args);
			$field->build_field();	
	    }


	     /**
		 * Generates an author field
		 *
		 * @since 1.0
		 */   
	    function author_field( $args ) {
	    	$defaults = array(
					'title' => '',
					'format' => 'select',
					'authors' => array()
				);

	    	$args = wp_parse_args($args, $defaults);
	    	$title = $args['title'];
	    	$format = $args['format'];
	    	$authors_list = $args['authors'];
	    	$selected_authors = array();

	    	if (isset($this->selected_authors)) {
	    		$selected_authors = $this->selected_authors;
	    	}

	    	$the_authors_list = array();

			if (count($authors_list) < 1) {
					$authors = get_users();
					foreach ($authors as $author) {
						$the_authors_list[$author->ID] = $author->display_name;
					}
			} else {
				foreach ($authors_list as $author) {
					if (get_userdata($author)) {
						$user = get_userdata($author);
						$the_authors_list[$author] = $user->display_name;
					}
				}
			}

			$args['values'] = $the_authors_list;

			$field = new WPAS_Field('a', $args);
			$field->build_field();

	    }

	     /**
		 * Generates a post type field
		 *
		 * @since 1.0
		 */   
	    function post_type_field( $args ) {
	    	$defaults = array(
					'title' => '',
					'format' => 'select',
					'values' => array('post' => 'Post', 'page' => 'Page')
				);

	    	$args = wp_parse_args($args, $defaults);
	    	$title = $args['title'];
	    	$format = $args['format'];
	    	$values = $args['values'];
	    	$selected_values = array();

	    	if (isset($this->selected_post_types)) {
	    		$selected_values = $this->selected_post_types;
	    	}

	    	$the_authors_list = array();

			if (count($values) < 1) {
				$post_types = get_post_types(array('public' => true)); 
				foreach ( $post_types as $post_type ) {
					$obj = get_post_type_object($post_type);
					$post_type_id = $obj->name;
					$post_type_name = $obj->labels->name;
					$values[$post_type_id] = $post_type_name;
				}
			} 

			$args['values'] = $values;

			$field = new WPAS_Field('ptype', $args);
			$field->build_field();
			
	    }

	    /**
		 * Generates a date field
		 *
		 * @since 1.0
		 */   
	    function date_field( $args ) {
	    	$defaults = array(
			'title' => '',
			'id' => 'date_y',
			'format' => 'select',
			'date_type' => 'year',
			'values' => array() );

			$args = wp_parse_args($args, $defaults);
			extract($args);

			$selected_values = array();
			$months = array(1 => 'January', 
							2 => 'Feburary', 
							3 => 'March', 
							4 =>'April', 
							5 => 'May', 
							6 => 'June', 
							7 => 'July', 
							8 => 'August', 
							9 => 'September', 
							10 => 'October', 
							11 => 'November', 
							12 => 'December');

			$days = array();
			for ($i=0;$i<31;$i++) {
				$days[$i + 1] = $i + 1;
			}

			switch ($date_type) {
				case ('year') :
					if (count($values) < 1) {
						$d_values = $this->get_years();
					}
					$id = 'date_y';
					break;
				case ('month') :
					if (count($values) < 1) {
						$d_values = $this->get_months();
					}
					$id = 'date_m';
					break;
				case ('day') :
					if (count($values) < 1) {
						$d_values = $days;
					}
					$id = 'date_d';
			}

			if (empty($values)) {
				$args['values'] = $d_values;
			}
		
			$args['id'] = $id;

			$field = new WPAS_Field($id, $args);
			$field->build_field();

	    }

		/**
		 * Generates an HTML content field
		 * 
		 * This "field" is not used for data entry but rather for inserting
		 * custom markup within the form body.
		 *
		 * @since 1.0
		 */   
	    function html_field( $args ) {
	    	$defaults = array('id'=>1, 'value' => '');
	    	extract(wp_parse_args($args, $defaults));

	    	$args['format'] = 'html';
	    	$args['values'] = $value;

			$field = new WPAS_Field('html-'.$id, $args);
			$field->build_field();
	    }

		/**
		 * Generates a generic form field
		 * 
		 * Used for creating form fields that do not affect
		 * the WP_Query object
		 *
		 * @since 1.0
		 */   
	    function generic_field( $args ) {
	    	$defaults = array();
	    	extract(wp_parse_args($args, $defaults));

	    	if (isset($id) && !empty($id)) {
				$field = new WPAS_Field($id, $args);
				$field->build_field();
			}
	    }

		/**
		 * Generates a generic form field
		 * 
		 * Used for creating form fields that do not affect
		 * the WP_Query object
		 *
		 * @since 1.0
		 */  
	    function posts_per_page_field( $args ) {
	    	$defaults = array(
	    		'format' => 'text',
	    		'value' => ''
	    		);

	    	$args = wp_parse_args($args, $defaults);
	    	$field = new WPAS_Field('posts_per_page', $args);
	    	$field->build_field();
	    }

		/**
		 * Builds the tax_query component of our WP_Query object based on form input
		 *
		 * @since 1.0
		 */
	    function build_tax_query() {
	    	$query = $this->wp_query_args;
	    	$taxonomies = $this->selected_taxonomies;
	    	

	    	foreach ($taxonomies as $tax => $terms) {
	    		$term_slugs = array(); // used when term_format is set to 'name'
	    		$term_format = $this->term_formats[$tax];
	    		$has_error = false;
	    		if (isset($this->term_formats[$tax]))
	    			$term_format = $this->term_formats[$tax];
	    		else
	    			$term_format = 'slug';

	    		if ($term_format == 'name') {
	    			if (!is_array($terms)) 
	    				$terms = array($terms);

	    			foreach ($terms as $term) {
	    				$the_term = get_term_by('name', $term, $tax);
	    				if ($the_term) {
	    					$term_slugs[] = $the_term->slug;
	    				} else {
	    					$has_error = true;
	    					$term_slugs[] = $term; // even if term invalid, we have to add it anyway to produce 0 results
	    				}
	    			}

	    			$terms = $term_slugs;
	    			$term_format = 'slug';
	    		} else if (!($term_format == 'slug') && !($term_format == 'id')) {
	    			$this->error('Invalid term_format for taxonomy "'.$tax.'"');
	    			continue;
	    		}

	    		$this->wp_query_args['tax_query'][] = array(	
				    									'taxonomy' => $tax,
														'field' => $term_format,
														'terms' => $terms,
														'operator' => $this->taxonomy_operators[$tax]
														);
	    	} // endforeach $taxonomies

	    }

		/**
		 * Builds the meta_query component of our WP_Query object based on form input
		 *
		 * @since 1.0
		 */
	    function build_meta_query() {
	    	$meta_keys = $this->selected_meta_keys;

	    	foreach ($meta_keys as $key => $values) {
	    		$this->wp_query_args['meta_query'][] = array(	
				    									'key' => $key,
														'value' => $values,
														'compare' => $this->meta_keys[$key]['compare'],
														'type' => $this->meta_keys[$key]['data_type']
														);
	    	}
	 
	    }

		/**
		 * Processes form input and modifies the query accordingly
		 *
		 * @since 1.0
		 */
	    function process_form_input() {

	    	foreach ($_REQUEST as $request => $value) {

	    		if ($value) {
	    			$this->selected_fields[$request] = $value;
		    		if (substr($request, 0, 4) == 'tax_') {
		    			$tax = $this->tax_from_arg($request);
		    			if ($tax) {
		    				$this->selected_taxonomies[$tax] = $value;
		    			}
		    		} elseif (substr($request, 0, 5) == 'meta_') {

		    			$meta = $this->meta_from_arg($request);
		    			if ($meta) {
		    				$this->selected_meta_keys[$meta] = $value;
		    			}
		    		} else {
		    			$selected = array();
		    			if (!is_array($value)) {
							$selected[] = $value;
						} else {
							foreach ($value as $the_value) {
								$selected[] = $the_value;
							}
						}

		    			switch($request) {
		    				case('a') :
		    					$this->wp_query_args['author'] = implode(',', $selected);
		    					break;
		    				case('ptype') :
		    					$this->wp_query_args['post_type'] = $selected;
		    					break;
		    				case('order') :
		    					$this->wp_query_args['order'] = implode(',', $selected);
		    					break;
		    				case('orderby') :
		    					$this->wp_query_args['orderby'] = implode(',', $selected);
		    					break;
		    				case('date_m') :
		    					$year = strstr(reset($selected), '-', true);
		    					$month = substr(strstr(reset($selected), '-'), 1);
		    					$this->wp_query_args['monthnum'] = $month;
		    					$this->wp_query_args['year'] = $year;
		    					break;    	
		    				case('date_y') :
								$this->wp_query_args['year'] = implode(',', $selected);
		    					break;
		    				case('date_d') :
		    					$this->wp_query_args['day'] = implode(',', $selected);
		    					break;	
		    				case('posts_per_page') :
		    					$ppp = implode(',', $selected);
		    					$this->wp_query_args['posts_per_page'] = intval($ppp);
		    					break;	
		    				case('search_query') :
		    					$this->wp_query_args['s'] = implode(',', $selected);
		    					break;	
		    			} // switch

		    		} // endif
		    	} // endif ($value)

	    	}// endforeach $_REQUEST

	    }

		/**
		 * Converts certain orderby keys into formats compatible with WP_Query
		 *
		 * @since 1.0
		 */
	    function process_orderby() {
			if (isset($this->wp_query_args['orderby'])) {
				switch($this->wp_query_args['orderby']) {
					case 'post_title' :
					case 'post_author' :
					case 'post_date' :
					case 'post_modified' :
					case 'post_parent' :
					case 'post_name' :
						$this->orderby_relevanssi = $this->wp_query_args['orderby'];
						$this->wp_query_args['orderby'] = substr($this->wp_query_args['orderby'], 5);
						break;
				}	
			}
	    }

		/**
		 * Initializes a WP_Query object with the given search parameters
		 *
		 * @since 1.0
		 */
	    function query() {
	    	$this->build_tax_query();
	    	$this->build_meta_query();
	    	$this->process_orderby();

	    	// Apply pagination
	    	if ( get_query_var('paged') ) {
			    $paged = get_query_var('paged');
			} else if ( get_query_var('page') ) {
			    $paged = get_query_var('page');
			} else {
			    $paged = 1;
			}
			$this->wp_query_args['paged'] = $paged;

	    	$this->wp_query = new WP_Query($this->wp_query_args);

	    	$query = $this->wp_query;
	    	$query->query_vars['post_type'] = $this->wp_query_args['post_type'];

	    	if ($this->relevanssi) {
	    		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
				if (!empty($_REQUEST['search_query']) && is_plugin_active('relevanssi/relevanssi.php')) {
					relevanssi_do_query($query);
				}
	    	}

	    	if (defined('WPAS_DEBUG') && WPAS_DEBUG) {
	    		echo '<pre>';
	    		print_r($query);
	    		echo '</pre>';
	    	}

	    	return $query;
	    }


		/**
		 * Displays pagination links
		 *
		 * @since 1.0
		 */
		function pagination( $args = '' ) {
			global $wp_query;
			$current_page = max(1, get_query_var('paged'));
			$total_pages = $wp_query->max_num_pages;

			if ( is_search() || is_post_type_archive() ) {  // Special treatment needed for search & archive pages
				$big = '999999999';
				$base = str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) );
			} else {
				$base = get_pagenum_link(1) . '%_%';
			}

			$defaults = array(
							'base' => $base,
							'format' => 'page/%#%',
							'current' => $current_page,
							'total' => $total_pages
						);

			$args = wp_parse_args($args, $defaults);

			if ($total_pages > 1){
				echo '<div class="pagination">';
				echo paginate_links($args);
				echo '</div>';
			}

		}

		/**
		 * Displays range of results displayed on the current page.
		 *
		 * @since 1.0
		 */
		function results_range( $args = array() ) {
			global $wp_query;

			$defaults = array(
							'pre' => '',
							'marker' => '-',
							'post' => ''
						);	

			$args = wp_parse_args($args, $defaults);	
			extract($args);

			$total = $wp_query->found_posts;
			$count = $wp_query->post_count;
			$query = $wp_query->query;
			$ppp = $query['posts_per_page'];
			$page =  get_query_var('paged');
			$range = 1;
			$current_post = $wp_query->current_post + 1;

			$range = $page;
			if ($ppp > 1) {
				$i = 1 + (($page - 1)*2);
				$j = $i + ($ppp - 1);
				$range = sprintf('%d%s%d', $i, $marker, $j);
				if ($j > $total) {
					$range = $total;
				} 
			}

			if ($count < 1) {
				$range = 0;
			}

			$output = sprintf('<span>%s</span> <span>%s</span> <span>%s</span>', $pre, $range, $post);

			return $output;
		}

		/**
		 * Accepts a term slug & taxonomy and returns the ID of that term
		 *
		 * @since 1.0
		 */
	    function term_slug_to_id( $term, $taxonomy ) {
	    	$term = get_term_by('slug', $term, $taxonomy);
	    	return $term->term_id;
	    }

	    /**
		 * Takes a specially-formatted taxonomy argument and returns the taxonomy name
		 *
		 * @since 1.0
		 */
	    function tax_from_arg( $arg ) {
	    	if (substr($arg, 0, 4) == 'tax_'){
	    		$tax = substr($arg, 4, strlen($arg) - 4);
	    		if (taxonomy_exists($tax)) {
	    			return $tax;
	    		} else {
	    			return false;
	    		}
	    	} else {
	    		return false;
	    	}

	    }

	    /**
		 * Takes a specially-formatted meta_key argument and returns the meta_key
		 *
		 * @since 1.0
		 */
	    function meta_from_arg( $arg ) {
	    	if (substr($arg, 0, 5) == 'meta_'){
	    		$meta = substr($arg, 5, strlen($arg) - 5);
	    		return $meta;
	    	} else {
	    		return false;
	    	}

	    }

	    /**
		 * Returns an array of years in which posts have been published
		 *
		 * @since 1.0
		 */
	    function get_years() {
	    	$post_type = $this->wp_query_args['post_type'];
	    	$posts = get_posts(array('numberposts' => -1, 'post_type' => $post_type));
	        $previous_year = "";

	        $display_format = "F Y";
	        $compare_format = "Ym";

	        $years = array();

	        foreach($posts as $post) {
	            $post_date = strtotime($post->post_date);
	            $current_ym_display = date_i18n($display_format, $post_date);
	            $current_ym_value = date($compare_format, $post_date);
	            $current_year = date("Y", $post_date);
	            //echo $current_year.'<br/>';
	            $current_month = date("m", $post_date);
	            if ($previous_year != $current_year) {
	            	if (empty($years[$current_year]))
	            		$years[$current_year] = $current_year;
	            }
	            $previous_year = $current_year;
	        }

	        return $years;
	    }

	    /**
		 * Returns an array of months in which posts have been published.
		 *
		 * Dates are formatted as YYYY-MM.
		 *
		 * @since 1.0
		 */
	    function get_months() {
	    	$post_type = $this->wp_query_args['post_type'];
	    	$posts = get_posts(array('numberposts' => -1, 'post_type' => $post_type));
	        $previous_ym_display = "";
	        $previous_ym_value = "";        
	        $previous_year = "";
	        $previous_month = "";
	        $count = 0;

	        $display_format = "M Y";
	        $compare_format = "Y-m";

	        $dates = array();

	        foreach($posts as $post) {
	            $post_date = strtotime($post->post_date);
	            $current_ym_display = date_i18n($display_format, $post_date);
	            $current_ym_value = date($compare_format, $post_date);
	            $current_year = date("Y", $post_date);
	            $current_month = date("m", $post_date);
	            if ($previous_ym_value != $current_ym_value) {
	            	$dates[$current_ym_value] = $current_ym_display;
	            }
	            $previous_ym_display = $current_ym_display;
	            $previous_ym_value = $current_ym_value;
	            $previous_year = $current_year;
	            $previous_month = $current_month;
	        }

	        return $dates;

	    }

	    /**
	     *  Displays an error message for debugging
	     *
	     *  @since 1.0
	     */
	    function error($msg = false) {
	    	if ($msg) {
				if (defined('WPAS_DEBUG') && WPAS_DEBUG) {
					echo '<p><strong>WPAS Error: </strong> ' . $msg . '</p>';
				}
	    	}
	    }

	} // class
} // endif

new WP_Advanced_Search();