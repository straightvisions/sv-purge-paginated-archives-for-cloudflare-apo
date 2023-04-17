<?php
/*
Version: 1.0.00
Plugin Name: SV Purge Paginated Archives for Cloudflare APO
Text Domain: sv_purge_paginated_archives_for_cloudflare_apo
Description: Hotfix for https://github.com/cloudflare/Cloudflare-WordPress/issues/394
Plugin URI: https://straightvisions.com/
Author: straightvisions GmbH
Author URI: https://straightvisions.com
Domain Path: /languages
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0-standalone.html
*/

	namespace sv_purge_paginated_archives_for_cloudflare_apo;

	class cloudflare {
		public function __CONSTRUCT() {
			add_filter('cloudflare_purge_by_url', array($this, 'cloudflare_purge_by_url'), 10, 2);
		}
		public function cloudflare_purge_by_url(array $listofurls, int $postId){
			$postType = get_post_type($postId);

			//Purge taxonomies terms and feeds URLs
			$postTypeTaxonomies = get_object_taxonomies($postType);

			foreach ($postTypeTaxonomies as $taxonomy) {
				// Only if taxonomy is public
				$taxonomy_data = get_taxonomy($taxonomy);
				if ($taxonomy_data instanceof WP_Taxonomy && false === $taxonomy_data->public) {
					continue;
				}

				$terms = get_the_terms($postId, $taxonomy);

				if (empty($terms) || is_wp_error($terms)) {
					continue;
				}

				foreach ($terms as $term) {
					$termLink = get_term_link($term);
					$termFeedLink = get_term_feed_link($term->term_id, $term->taxonomy);
					if (!is_wp_error($termLink) && !is_wp_error($termFeedLink)) {
						$listofurls = array_merge(
							$listofurls,
							$this->urls_paginated($termLink),
							$this->urls_paginated($termFeedLink));
					}
				}
			}

			// Author URL
			$listofurls = array_merge(
				$listofurls,
				$this->urls_paginated(get_author_posts_url(get_post_field('post_author', $postId))),
				$this->urls_paginated(get_author_feed_link(get_post_field('post_author', $postId)))
			);

			// Archives and their feeds
			if (get_post_type_archive_link($postType) == true) {
				$listofurls = array_merge(
					$listofurls,
					$this->urls_paginated(get_post_type_archive_link($postType)),
					$this->urls_paginated(get_post_type_archive_feed_link($postType))
				);
			}

			return $listofurls;
		}
		public function urls_paginated(string $link): array{
			$links = array();
			foreach (range(2, apply_filters('sv_purge_paginated_archives_for_cloudflare_apo_max_limit', 20)) as $page_number) {
				$links[] = $link . 'page/' . $page_number . '/';
			}

			return $links;
		}
	}
	
	new cloudflare();