<?php

/**
 * @group Media
 */
class FormatMetadataTest extends MediaWikiMediaTestCase {

	protected function setUp() {
		parent::setUp();

		$this->checkPHPExtension( 'exif' );
		$this->setMwGlobals( 'wgShowEXIF', true );
	}

	/**
	 * @covers File::formatMetadata
	 */
	public function testInvalidDate() {
		$file = $this->dataFile( 'broken_exif_date.jpg', 'image/jpeg' );

		// Throws an error if bug hit
		$meta = $file->formatMetadata();
		$this->assertNotEquals( false, $meta, 'Valid metadata extracted' );

		// Find date exif entry
		$this->assertArrayHasKey( 'visible', $meta );
		$dateIndex = null;
		foreach ( $meta['visible'] as $i => $data ) {
			if ( $data['id'] == 'exif-datetimeoriginal' ) {
				$dateIndex = $i;
			}
		}
		$this->assertNotNull( $dateIndex, 'Date entry exists in metadata' );
		$this->assertEquals( '0000:01:00 00:02:27',
			$meta['visible'][$dateIndex]['value'],
			'File with invalid date metadata (T31471)' );
	}

	/**
	 * @param mixed $input
	 * @param mixed $output
	 * @dataProvider provideResolveMultivalueValue
	 * @covers FormatMetadata::resolveMultivalueValue
	 */
	public function testResolveMultivalueValue( $input, $output ) {
		$formatMetadata = new FormatMetadata();
		$class = new ReflectionClass( 'FormatMetadata' );
		$method = $class->getMethod( 'resolveMultivalueValue' );
		$method->setAccessible( true );
		$actualInput = $method->invoke( $formatMetadata, $input );
		$this->assertEquals( $output, $actualInput );
	}

	public function provideResolveMultivalueValue() {
		return [
			'nonArray' => [
				'foo',
				'foo'
			],
			'multiValue' => [
				[ 'first', 'second', 'third', '_type' => 'ol' ],
				'first'
			],
			'noType' => [
				[ 'first', 'second', 'third' ],
				'first'
			],
			'typeFirst' => [
				[ '_type' => 'ol', 'first', 'second', 'third' ],
				'first'
			],
			'multilang' => [
				[
					'en' => 'first',
					'de' => 'Erste',
					'_type' => 'lang'
				],
				[
					'en' => 'first',
					'de' => 'Erste',
					'_type' => 'lang'
				],
			],
			'multilang-multivalue' => [
				[
					'en' => [ 'first', 'second' ],
					'de' => [ 'Erste', 'Zweite' ],
					'_type' => 'lang'
				],
				[
					'en' => 'first',
					'de' => 'Erste',
					'_type' => 'lang'
				],
			],
		];
	}
}
