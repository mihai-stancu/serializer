<?php

/*
 * Copyright (c) 2015 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 * code in the LICENSE.md file.
 */

namespace MS\SerializerBundle\Tests\Serializer\Encoder;

use Faker\Factory;
use Faker\Generator;
use Faker\Provider\en_GB\Payment;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;

class BenchmarkTest extends \PHPUnit_Framework_TestCase
{
    protected static $encoders = array(
        'bencode' => 'MS\SerializerBundle\Serializer\Encoder\BencodeEncoder',
        'bson' => 'MS\SerializerBundle\Serializer\Encoder\BsonEncoder',
        'cbor' => 'MS\SerializerBundle\Serializer\Encoder\CborEncoder',
        'export' => 'MS\SerializerBundle\Serializer\Encoder\ExportEncoder',
        'form' => 'MS\SerializerBundle\Serializer\Encoder\FormEncoder',
        'igbinary' => 'MS\SerializerBundle\Serializer\Encoder\IgbinaryEncoder',
        'ini' => 'MS\SerializerBundle\Serializer\Encoder\IniEncoder',
        'json' => 'Symfony\Component\Serializer\Encoder\JsonEncoder',
        'msgpack' => 'MS\SerializerBundle\Serializer\Encoder\MsgpackEncoder',
        'rison' => 'MS\SerializerBundle\Serializer\Encoder\RisonEncoder',
        //'sereal' => 'MS\SerializerBundle\Serializer\Encoder\SerealEncoder',
        'serialize' => 'MS\SerializerBundle\Serializer\Encoder\SerializeEncoder',
        //'smile' => 'MS\SerializerBundle\Serializer\Encoder\SmileEncoder',
        'tnetstring' => 'MS\SerializerBundle\Serializer\Encoder\TnetstringEncoder',
        'ubjson' => 'MS\SerializerBundle\Serializer\Encoder\UbjsonEncoder',
        'xml' => 'Symfony\Component\Serializer\Encoder\XmlEncoder',
        'yaml' => 'MS\SerializerBundle\Serializer\Encoder\YamlEncoder',
    );

    protected $numberOfIterations = 100;
    protected $numberOfProducts = 10;
    protected $numberOfInvoices = 1;

    /** @var  \PDO */
    public $pdo;

    #region Fakers

    /**
     * @param Generator $faker
     * @param bool      $company
     *
     * @return array
     */
    public function fakePerson($faker, $company = true)
    {
        return $company ? array(
                'id' => $faker->uuid,
                'name' => $faker->company,
                'email' => $faker->companyEmail,
                'address' => $faker->address,
                'telephone' => $faker->phoneNumber,
                'faxnumber' => $faker->phoneNumber,
                'landline' => $faker->phoneNumber,
                'representative' => array(
                    'id' => $faker->uuid,
                    'first_name' => $faker->firstName,
                    'last_name' => $faker->lastName,
                    'address' => $faker->address,
                    'telephone' => $faker->phoneNumber,
                    'faxnumber' => $faker->phoneNumber,
                    'landline' => $faker->phoneNumber,
                ),
            ) : array(
                'id' => $faker->uuid,
                'first_name' => $faker->firstName,
                'last_name' => $faker->lastName,
                'address' => $faker->address,
                'telephone' => $faker->phoneNumber,
                'faxnumber' => $faker->phoneNumber,
                'landline' => $faker->phoneNumber,
            );
    }

    /**
     * @param Generator $faker
     *
     * @return array
     */
    public function fakeProduct($faker)
    {
        return array(
            'id' => $faker->uuid,
            'name' => $faker->name,
            'weight' => $faker->randomNumber(),
            'ean13' => $faker->ean13,
        );
    }

    #endregion

    public function setUp()
    {
        $this->pdo = new \PDO('sqlite:'.__DIR__.'/../bench.sq3');

        $this->pdo->exec(
            '
                CREATE TABLE IF NOT EXISTS encoder_bench (
                    id integer primary key,

                    format varchar(255),

                    dataLength int,
                    dataTime double,

                    encodedLength int,
                    encodingTime double,
                    decodingTime double,

                    gzipEncodedLength int,
                    gzipEncodingTime double,
                    gzipDecodingTime double,

                    bzipEncodedLength int,
                    bzipEncodingTime double,
                    bzipDecodingTime double
                );

                CREATE VIEW IF NOT EXISTS encoder_bench_view AS
                    SELECT format,
                           ROUND(AVG(encodedLength*100/dataLength), 4) as encoded,
                           ROUND(AVG(gzipEncodedLength*100/dataLength), 4) as gzipEncoded,
                           ROUND(AVG(bzipEncodedLength*100/dataLength), 4) as bzipEncoded,
                           ROUND(AVG(encodingTime*100/dataTime), 4) as encoding,
                           ROUND(AVG(gzipEncodingTime*100/dataTime), 4) as gzipEncoding,
                           ROUND(AVG(bzipEncodingTime*100/dataTime), 4) as bzipEncoding,
                           ROUND(AVG(decodingTime*100/dataTime), 4) as decoding,
                           ROUND(AVG(gzipDecodingTime*100/dataTime), 4) as gzipDecoding,
                           ROUND(AVG(bzipDecodingTime*100/dataTime), 4) as bzipDecoding
                        FROM encoder_bench
                        GROUP BY format;
            '
        );
    }

    public function tearDown()
    {
        $this->pdo = null;
    }

    /**
     * @return array
     */
    public function dataProviderEncoders()
    {
        $tests = array();

        /** @var Generator $faker */
        $faker = Factory::create();
        $faker->addProvider(new Payment($faker));

        for ($k = 0; $k < $this->numberOfIterations; ++$k) {
            $invoices = array();
            for ($i = 0; $i < $this->numberOfInvoices; ++$i) {
                $invoices[$i] = array(
                    'vendor' => $this->fakePerson($faker, true),
                    'customer' => $this->fakePerson($faker, mt_rand(0, 1)),
                    'products' => array(),
                );
                for ($j = 0; $j < $this->numberOfProducts; ++$j) {
                    $invoice['products'][$j] = $this->fakeProduct($faker);
                }
            }

            foreach (static::$encoders as $format => $encoderName) {
                if (!method_exists($encoderName, 'isInstalled') or $encoderName::isInstalled()) {

                    /** @var EncoderInterface|DecoderInterface $encoder */
                    $encoder = new $encoderName();

                    $tests[] = array($encoder, $format, $invoices);
                }
            }
        }

        return $tests;
    }

    /**
     * @dataProvider dataProviderEncoders
     *
     * @param EncoderInterface|DecoderInterface $encoder
     * @param string                            $format
     * @param array                             $data
     */
    public function testEncoder($encoder, $format, $data)
    {
        $bench = Bench::create($format, $encoder, $this->numberOfIterations, $data);

        $insert = '
            INSERT INTO encoder_bench
                VALUES (null, \''.implode("', '", (array) $bench).'\');
        ';
        $this->pdo->exec($insert);
    }
}

class Bench
{
    public $format;

    public $dataLength;
    public $dataTime;

    public $encodedLength;
    public $encodingTime;
    public $decodingTime;

    public $gzipEncodedLength;
    public $gzipEncodingTime;
    public $gzipDecodingTime;

    public $bzipEncodedLength;
    public $bzipEncodingTime;
    public $bzipDecodingTime;

    /**
     * @param string                            $format
     * @param EncoderInterface|DecoderInterface $encoder
     * @param int                               $iterations
     * @param mixed                             $data
     *
     * @return self
     */
    public static function create($format, $encoder, $iterations, $data)
    {
        $bench = new self();

        /*Raw****************************************************************************/

        $bench->format = $format;
        list($keysLength, $dataLength) = $bench->measure($data);
        $bench->dataTime = $bench->measureTime($iterations, array($bench, 'measure'), array($data));
        $bench->dataLength = $keysLength + $dataLength;

        /*Encoded************************************************************************/

        $encoded = $encoder->encode($data, $format);
        $bench->encodedLength = strlen($encoded);
        $bench->encodingTime = $bench->measureTime($iterations, array($encoder, 'encode'), array($data, $format));
        $bench->decodingTime = $bench->measureTime($iterations, array($encoder, 'decode'), array($encoded, $format));

        /*GZip***************************************************************************/

        $gzipEncoded = gzencode($encoded);
        $bench->gzipEncodedLength = strlen($gzipEncoded);
        $bench->gzipEncodingTime = $bench->encodingTime + $bench->measureTime($iterations, 'gzencode', array($encoded));
        $bench->gzipDecodingTime = $bench->decodingTime + $bench->measureTime($iterations, 'gzdecode', array($gzipEncoded));

        /*BZip***************************************************************************/

        $bzipEncoded = bzcompress($encoded);
        $bench->bzipEncodedLength = strlen($bzipEncoded);
        $bench->bzipEncodingTime = $bench->encodingTime + $bench->measureTime($iterations, 'bzcompress', array($encoded));
        $bench->bzipDecodingTime = $bench->decodingTime + $bench->measureTime($iterations, 'bzdecompress', array($bzipEncoded));

        return $bench;
    }

    #region Measurements

    public function measureTime($iterations, $callback, $args)
    {
        $before = microtime(true);

        for ($i = 0; $i < $iterations; ++$i) {
            call_user_func_array($callback, $args);
        }

        $after = microtime(true);

        return ($after - $before) * 1000 / $iterations;
    }

    /**
     * @param int   $total
     * @param mixed $value
     * @param bool  $keys
     *
     * @return mixed
     */
    public function measureItem($total, $value, $keys)
    {
        switch (gettype($value)) {
            case 'null':
            case 'boolean':
            case 'integer':
            case 'float':
            case 'double':
                return $total + log(PHP_INT_MAX, 2);

            case 'string':
                return $total + strlen($value);

            case 'array':
                list($k, $v) = $this->measure($value);

                return $total + ($keys ? $k : $v);
        }
    }

    /**
     * @param $total
     * @param $value
     *
     * @return mixed
     */
    public function measureKeysLength($total, $value)
    {
        return $this->measureItem($total, $value, true);
    }

    /**
     * @param $total
     * @param $value
     *
     * @return mixed
     */
    public function measureDataLength($total, $value)
    {
        return $this->measureItem($total, $value, false);
    }

    public function measure($data)
    {
        $keysLength = array_reduce(array_keys($data), array($this, 'measureKeysLength'));
        $dataLength = array_reduce(array_values($data), array($this, 'measureDataLength'));

        return array($keysLength, $dataLength);
    }

    #endregion
}
