<?php
/**
 * Demo copy for the Smart Search page.
 *
 * @package WPVDB_Smart_Search
 */

namespace WPVDB_Smart_Search;

defined( 'ABSPATH' ) || exit;

/**
 * Provides localized UI content that is not tied to rendering logic.
 */
class Content {
	const EXAMPLES_PER_LOAD = 5;

	/**
	 * Localized content for the page bootstrap config.
	 *
	 * @return array
	 */
	public static function page_content() {
		return [
			'examples_pool'    => [
				[ __( 'market jitters', 'wpvdb-smart-search' ), __( 'markets reacting to economic uncertainty', 'wpvdb-smart-search' ) ],
				[ __( 'diplomatic tension', 'wpvdb-smart-search' ), __( 'diplomatic tension between major powers', 'wpvdb-smart-search' ) ],
				[ __( 'celebrity charity', 'wpvdb-smart-search' ), __( 'celebrity charity efforts', 'wpvdb-smart-search' ) ],
				[ __( 'extreme weather', 'wpvdb-smart-search' ), __( 'extreme weather events', 'wpvdb-smart-search' ) ],
				[ __( 'greenwashing', 'wpvdb-smart-search' ), __( 'corporate hypocrisy and greenwashing', 'wpvdb-smart-search' ) ],
				[ __( 'public figures', 'wpvdb-smart-search' ), __( 'public figure controversies', 'wpvdb-smart-search' ) ],
				[ __( 'generational shift', 'wpvdb-smart-search' ), __( 'generational shifts in attitudes', 'wpvdb-smart-search' ) ],
				[ __( 'social media', 'wpvdb-smart-search' ), __( 'social media shaping public opinion', 'wpvdb-smart-search' ) ],
				[ __( 'veterans’ benefits', 'wpvdb-smart-search' ), __( 'veterans and their benefits', 'wpvdb-smart-search' ) ],
				[ __( 'school safety', 'wpvdb-smart-search' ), __( 'school safety debates', 'wpvdb-smart-search' ) ],
				[ __( 'animal welfare', 'wpvdb-smart-search' ), __( 'animal welfare stories', 'wpvdb-smart-search' ) ],
				[ __( 'rebuilding', 'wpvdb-smart-search' ), __( 'rebuilding after natural disasters', 'wpvdb-smart-search' ) ],
				[ __( 'missing persons', 'wpvdb-smart-search' ), __( 'missing persons cases that captured public attention', 'wpvdb-smart-search' ) ],
				[ __( 'Olympic moments', 'wpvdb-smart-search' ), __( 'Olympic Games triumphs and athletes stories', 'wpvdb-smart-search' ) ],
				[ __( 'plane crashes', 'wpvdb-smart-search' ), __( 'commercial aviation disasters and investigations', 'wpvdb-smart-search' ) ],
				[ __( 'police misconduct', 'wpvdb-smart-search' ), __( 'complaints of police misconduct and excessive force', 'wpvdb-smart-search' ) ],
				[ __( 'wildfires', 'wpvdb-smart-search' ), __( 'wildfires destroying communities and homes', 'wpvdb-smart-search' ) ],
				[ __( 'medical breakthroughs', 'wpvdb-smart-search' ), __( 'medical research breakthroughs and new treatments', 'wpvdb-smart-search' ) ],
				[ __( 'cyber attacks', 'wpvdb-smart-search' ), __( 'cyberattacks and major data breaches', 'wpvdb-smart-search' ) ],
				[ __( 'abuse scandals', 'wpvdb-smart-search' ), __( 'institutions covering up sexual abuse', 'wpvdb-smart-search' ) ],
				[ __( 'wartime stories', 'wpvdb-smart-search' ), __( 'wartime experiences of soldiers and civilians', 'wpvdb-smart-search' ) ],
				[ __( 'wildlife conservation', 'wpvdb-smart-search' ), __( 'endangered wildlife and conservation efforts', 'wpvdb-smart-search' ) ],
				[ __( 'fertility advances', 'wpvdb-smart-search' ), __( 'advances in fertility treatments and reproductive medicine', 'wpvdb-smart-search' ) ],
				[ __( 'auto recalls', 'wpvdb-smart-search' ), __( 'major auto recalls and vehicle safety investigations', 'wpvdb-smart-search' ) ],
				[ __( 'royal family', 'wpvdb-smart-search' ), __( 'British royal family events and traditions', 'wpvdb-smart-search' ) ],
			],
			'examples_visible' => self::EXAMPLES_PER_LOAD,
			'placeholders'     => [
				__( 'Search by meaning, not keywords…', 'wpvdb-smart-search' ),
				__( 'Describe an idea, theme, or event…', 'wpvdb-smart-search' ),
				__( 'What kind of story are you after?', 'wpvdb-smart-search' ),
				__( 'Try a concept, a trend, or a mood…', 'wpvdb-smart-search' ),
				__( 'Ask for what you want, not what it’s called…', 'wpvdb-smart-search' ),
				__( 'What are you curious about today?', 'wpvdb-smart-search' ),
				__( 'Type a theme, not a keyword…', 'wpvdb-smart-search' ),
				__( 'Tell it like you’d tell a friend…', 'wpvdb-smart-search' ),
				__( 'Think fuzzy, not exact…', 'wpvdb-smart-search' ),
				__( 'A feeling, a trend, a half-remembered headline…', 'wpvdb-smart-search' ),
				__( 'Be vague. We’ll figure it out…', 'wpvdb-smart-search' ),
				__( 'Explain it like I’m a database…', 'wpvdb-smart-search' ),
				__( 'We accept mumbles…', 'wpvdb-smart-search' ),
				__( 'Don’t overthink it. Just type…', 'wpvdb-smart-search' ),
				__( 'Type a hunch…', 'wpvdb-smart-search' ),
				__( 'Whisper an idea into the void…', 'wpvdb-smart-search' ),
				__( 'Close enough works here…', 'wpvdb-smart-search' ),
				__( 'Bring your loosest query…', 'wpvdb-smart-search' ),
				__( 'Describe, don’t define…', 'wpvdb-smart-search' ),
				__( 'Ask it like a human, not a SQL query…', 'wpvdb-smart-search' ),
			],
		];
	}

	/**
	 * Return the full queries used by the prewarm command.
	 *
	 * @return array[]
	 */
	public static function example_queries() {
		return self::page_content()['examples_pool'];
	}
}
