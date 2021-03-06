<?php
/**
 * This file is part of RedisClient.
 * git: https://github.com/cheprasov/php-redis-client
 *
 * (C) Alexander Cheprasov <cheprasov.84@ya.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Test\Integration\Version2x6;

use RedisClient\Client\Version\RedisClient2x6;

/**
 * @see \RedisClient\Command\Traits\Version2x6\PubSubCommandsTrait
 */
class PubSubCommandsTest extends \PHPUnit_Framework_TestCase {

    const TEST_REDIS_SERVER_1 = TEST_REDIS_SERVER_2x6_1;

    /**
     * @var RedisClient2x6
     */
    protected static $Redis;

    /**
     * @var RedisClient2x6
     */
    protected static $Redis2;

    /**
     * @inheritdoc
     */
    public static function setUpBeforeClass() {
        static::$Redis = new RedisClient2x6([
            'server' =>  static::TEST_REDIS_SERVER_1,
            'timeout' => 2,
        ]);
        static::$Redis2 = new RedisClient2x6([
            'server' =>  static::TEST_REDIS_SERVER_1,
            'timeout' => 2,
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function tearDownAfterClass() {
        static::$Redis->flushall();
    }

    /**
     * @inheritdoc
     */
    protected function setUp() {
        static::$Redis->flushall();
    }

    /**
     * @see \RedisClient\Command\Traits\Version2x6\PubSubCommandsTrait::subscribe
     * @see \RedisClient\Command\Traits\Version2x6\PubSubCommandsTrait::unsubscribe
     */
    public function test_subscribe_and_unsubscribe() {
        $Redis = static::$Redis;
        $Redis2 = static::$Redis2;

        $time = time();
        $messages = [];
        $posts = [];

        $result = $Redis->subscribe('channel-foo',
            function($type, $channel, $message) use ($Redis2, $time, &$messages, &$posts) {
                if (time() - $time > 1 || count($messages) > 20) {
                    return false;
                }
                if (!isset($type)) {
                    sleep(1);
                    return true;
                }
                if ($type === 'message') {
                    $messages[] = $message;
                }
                $Redis2->publish('channel-foo', $post = md5(rand(1, 9999)));
                $Redis2->publish('channel-bar', md5(rand(1, 9999)));
                $posts[] = $post;

                return true;
            });

        $this->assertSame(['unsubscribe', 'channel-foo', 0], $result);
        array_pop($posts);
        $this->assertSame($posts, $messages);

        $this->assertSame(true, $Redis->set('foo', 'bar'));
        $this->assertSame('bar', $Redis->get('foo'));
        $this->assertSame(['foo'], $Redis->keys('*'));
    }

    /**
     * @see \RedisClient\Command\Traits\Version2x6\PubSubCommandsTrait::psubscribe
     * @see \RedisClient\Command\Traits\Version2x6\PubSubCommandsTrait::punsubscribe
     */
    public function test_psubscribe_and_punsubscribe() {
        $Redis = static::$Redis;
        $Redis2 = static::$Redis2;

        $time = time();
        $messages = [];
        $posts = [];

        $result = $Redis->psubscribe('channel-f*',
            function($type, $pattern, $channel, $message) use ($Redis2, $time, &$messages, &$posts) {
                if (time() - $time > 1 || count($messages) > 20) {
                    return false;
                }
                if (!isset($type)) {
                    sleep(1);
                    return true;
                }
                if ($type === 'pmessage') {
                    $messages[] = $message;
                }
                $Redis2->publish('channel-foo', $post = md5(rand(1, 9999)));
                $Redis2->publish('channel-bar', md5(rand(1, 9999)));
                $posts[] = $post;

                return true;
            });

        $this->assertSame(['punsubscribe', 'channel-f*', 0], $result);
        array_pop($posts);
        $this->assertSame($posts, $messages);

        $this->assertSame(true, $Redis->set('foo', 'bar'));
        $this->assertSame('bar', $Redis->get('foo'));
        $this->assertSame(['foo'], $Redis->keys('*'));
    }

}
