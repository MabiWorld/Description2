<?php

namespace MediaWiki\Extension\Description2;

use MediaWiki\MediaWikiServices;
use OutputPage;
use Parser;
use ParserOutput;
use PPFrame;

/**
 * Description2 – Adds meaningful description <meta> tag to MW pages and into the parser output
 *
 * @file
 * @ingroup Extensions
 * @author Daniel Friesen (http://danf.ca/mw/)
 * @copyright Copyright 2010 – Daniel Friesen
 * @license GPL-2.0-or-later
 * @link https://www.mediawiki.org/wiki/Extension:Description2 Documentation
 */

class Description2 {

	/**
	 * @param Parser $parser The parser.
	 * @param string $desc The description text.
	 */
	public static function setDescription( Parser $parser, $desc ) {
		$desc = preg_replace( '%\[\[SMW::(off|on)\]\]%i', '', $desc );
		$desc = preg_replace( '%<br\s*/?>%i', "\n", $desc );
		$desc = html_entity_decode( html_entity_decode( $desc ) );
		$parserOutput = $parser->getOutput();
		if ( method_exists( $parserOutput, 'getPageProperty' ) ) {
			// MW 1.38+
			if ( $parserOutput->getPageProperty( 'description' ) !== null ) {
				return;
			}
			$parserOutput->setPageProperty( 'description', $desc );
		} else {
			if ( $parserOutput->getProperty( 'description' ) !== false ) {
				return;
			}
			$parserOutput->setProperty( 'description', $desc );
		}
	}

	/**
	 * @link https://www.mediawiki.org/wiki/Manual:Hooks/ParserAfterTidy
	 * @param Parser &$parser The parser.
	 * @param string &$text The page text.
	 * @return bool
	 */
	public static function onParserAfterTidy( Parser &$parser, &$text ) {
		$desc = '';

		$pattern = '%<table\b[^>]*+>(?:(?R)|[^<]*+(?:(?!</?table\b)<[^<]*+)*+)*+</table>%i';
		$myText = preg_replace( $pattern, '', $text );

		$paragraphs = [];
		if ( preg_match_all( '#<p>.*?</p>#is', $myText, $paragraphs ) ) {
			foreach ( $paragraphs[0] as $paragraph ) {
				$paragraph = trim( strip_tags( $paragraph ) );
				if ( !$paragraph ) {
					continue;
				}
				$desc = $paragraph;
				break;
			}
		}

		if ( $desc ) {
			self::setDescription( $parser, $desc );
		}

		return true;
	}

	/**
	 * @param Parser &$parser The parser.
	 * @return bool
	 */
	public static function onParserFirstCallInit( Parser &$parser ) {
		$config = MediaWikiServices::getInstance()
			->getConfigFactory()
			->makeConfig( 'Description2' );
		if ( !$config->get( 'EnableMetaDescriptionFunctions' ) ) {
			// Functions and tags are disabled
			return true;
		}
		$parser->setFunctionHook(
			'description2',
			[ static::class, 'parserFunctionCallbackShow' ],
			Parser::SFH_OBJECT_ARGS
		);
		$parser->setFunctionHook(
			'description2hide',
			[ static::class, 'parserFunctionCallbackHide' ],
			Parser::SFH_OBJECT_ARGS
		);
		return true;
	}

	/**
	 * @param Parser $parser The parser.
	 * @param PPFrame $frame The frame.
	 * @param string[] $args The arguments of the parser function call.
	 * @return string
	 */
	public static function parserFunctionCallbackShow( Parser $parser, PPFrame $frame, $args ) {
		$desc = isset( $args[0] ) ? $frame->expand( $args[0] ) : '';
		self::setDescription( $parser, $desc );
		return $desc;
	}
	public static function parserFunctionCallbackHide( Parser $parser, PPFrame $frame, $args ) {
		$desc = isset( $args[0] ) ? $frame->expand( $args[0] ) : '';
		self::setDescription( $parser, $desc );
		return '';
	}

	/**
	 * @param OutputPage &$out The output page to add the meta element to.
	 * @param ParserOutput $parserOutput The parser output to get the description from.
	 */
	public static function onOutputPageParserOutput( OutputPage &$out, ParserOutput $parserOutput ) {
		// Export the description from the main parser output into the OutputPage
		if ( method_exists( $parserOutput, 'getPageProperty' ) ) {
			// MW 1.38+
			$description = $parserOutput->getPageProperty( 'description' );
		} else {
			$description = $parserOutput->getProperty( 'description' );
			if ( $description === false ) {
				$description = null;
			}
		}
		if ( $description !== null ) {
			$out->addMeta( 'description', $description );
		}
	}
}
