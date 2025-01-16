<?php

namespace Aura\Marshal;

use Aura\Marshal\Collection\GenericCollection;
use Aura\Marshal\Entity\GenericEntity;
use Aura\Marshal\Relation\Builder as RelationBuilder;
use Aura\Marshal\Type\Builder as TypeBuilder;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Test class for Manager.
 * Generated by PHPUnit on 2011-11-21 at 11:28:20.
 */
class RelationTest extends TestCase
{
    /**
     * @var Manager
     */
    protected $manager;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function set_up(): void
    {
        parent::set_up();
        $type_builder       = new TypeBuilder;
        $relation_builder   = new RelationBuilder;
        $types              = include __DIR__ . DIRECTORY_SEPARATOR . 'fixture_types.php';

        $this->manager      = new Manager($type_builder, $relation_builder, $types);
        $data               = include __DIR__ . DIRECTORY_SEPARATOR . 'fixture_data.php';
        foreach ($this->manager->getTypes() as $type) {
            $obj = $this->manager->{$type}->load($data[$type]);
        }
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tear_down(): void
    {
        parent::tear_down();
    }

    public function testNoRelationship(): void
    {
        $type_builder     = new TypeBuilder;
        $relation_builder = new RelationBuilder;
        $types            = include __DIR__ . DIRECTORY_SEPARATOR . 'fixture_types.php';

        $types['posts']['relation_names']['tags']['relationship'] = null;

        $this->manager = new Manager($type_builder, $relation_builder, $types);
        $this->expectException('Aura\Marshal\Exception');
        $this->manager->posts;
    }

    public function testNoForeignType(): void
    {
        $type_builder     = new TypeBuilder;
        $relation_builder = new RelationBuilder;
        $types            = include __DIR__ . DIRECTORY_SEPARATOR . 'fixture_types.php';

        $types['posts']['relation_names']['tags']['foreign_type'] = null;

        $this->manager = new Manager($type_builder, $relation_builder, $types);
        $this->expectException('Aura\Marshal\Exception');
        $this->manager->posts;
    }

    public function testNoNativeField(): void
    {
        $type_builder     = new TypeBuilder;
        $relation_builder = new RelationBuilder;
        $types            = include __DIR__ . DIRECTORY_SEPARATOR . 'fixture_types.php';

        $types['posts']['relation_names']['tags']['native_field'] = null;

        $this->manager = new Manager($type_builder, $relation_builder, $types);
        $this->expectException('Aura\Marshal\Exception');
        $this->manager->posts;
    }

    public function testNoForeignField(): void
    {
        $type_builder     = new TypeBuilder;
        $relation_builder = new RelationBuilder;
        $types            = include __DIR__ . DIRECTORY_SEPARATOR . 'fixture_types.php';

        $types['posts']['relation_names']['tags']['foreign_field'] = null;

        $this->manager = new Manager($type_builder, $relation_builder, $types);
        $this->expectException('Aura\Marshal\Exception');
        $this->manager->posts;
    }

    public function testNoThroughType(): void
    {
        $type_builder     = new TypeBuilder;
        $relation_builder = new RelationBuilder;
        $types            = include __DIR__ . DIRECTORY_SEPARATOR . 'fixture_types.php';

        $types['posts']['relation_names']['tags']['through_type'] = null;

        $this->manager = new Manager($type_builder, $relation_builder, $types);
        $this->expectException('Aura\Marshal\Exception');
        $this->manager->posts;
    }

    public function testNoThroughNativeField(): void
    {
        $type_builder     = new TypeBuilder;
        $relation_builder = new RelationBuilder;
        $types            = include __DIR__ . DIRECTORY_SEPARATOR . 'fixture_types.php';

        $types['posts']['relation_names']['tags']['through_native_field'] = null;

        $this->manager = new Manager($type_builder, $relation_builder, $types);
        $this->expectException('Aura\Marshal\Exception');
        $this->manager->posts;
    }

    public function testNoThroughForeignField(): void
    {
        $type_builder     = new TypeBuilder;
        $relation_builder = new RelationBuilder;
        $types            = include __DIR__ . DIRECTORY_SEPARATOR . 'fixture_types.php';

        $types['posts']['relation_names']['tags']['through_foreign_field'] = null;

        $this->manager = new Manager($type_builder, $relation_builder, $types);
        $this->expectException('Aura\Marshal\Exception');
        $this->manager->posts;
    }

    public function testGetForeignType(): void
    {
        $relation = $this->manager->posts->getRelation('author');
        $actual = $relation->getForeignType();
        $this->assertSame('authors', $actual);
    }

    public function testBelongsTo(): void
    {
        /**
         * @var GenericEntity $post
         */
        $post = $this->manager->posts->getEntity(1);
        $this->assertSame('1', $post->author->id);
        $this->assertSame('Anna', $post->author->name);
    }

    public function testHasOne(): void
    {
        /**
         * @var GenericEntity $post
         */
        $post = $this->manager->posts->getEntity(1);
        $this->assertSame('1', $post->meta->id);
        $this->assertSame('1', $post->meta->post_id);
        $this->assertSame('meta 1', $post->meta->data);
    }

    public function testHasMany(): void
    {
        /**
         * @var GenericEntity $post
         */
        $post = $this->manager->posts->getEntity(5);
        $this->assertSame(3, count($post->comments));

        $data  = include __DIR__ . DIRECTORY_SEPARATOR . 'fixture_data.php';
        $expect = [
            $data['comments'][3],
            $data['comments'][4],
            $data['comments'][5],
        ];

        foreach ($post->comments as $offset => $comment) {
            $this->assertSame($expect[$offset]['id'], $comment->id);
            $this->assertSame($expect[$offset]['post_id'], $comment->post_id);
            $this->assertSame($expect[$offset]['body'], $comment->body);
        }
    }

    public function testHasManyThrough(): void
    {
        /**
         * @var GenericEntity $post
         */
        $post = $this->manager->posts->getEntity(3);
        $this->assertSame(2, count($post->tags));

        $data  = include __DIR__ . DIRECTORY_SEPARATOR . 'fixture_data.php';
        $expect = [
            $data['tags'][2],
            $data['tags'][0],
        ];

        foreach ($post->tags as $offset => $tag) {
            $this->assertSame($expect[$offset]['id'], $tag->id);
            $this->assertSame($expect[$offset]['name'], $tag->name);
        }
    }

    public function testIteratorToArrayEntityHasRelationships(): void
    {
        /** @var GenericCollection */
        $posts = $this->manager->posts->getCollection($this->manager->posts->getIdentityValues());
        $postsArray = iterator_to_array($posts);

        $this->assertCount(3, $postsArray[0]->comments);
        $this->assertCount(2, $postsArray[0]->tags);
        $this->assertEquals('Anna', $postsArray[0]->author->name);
    }
}
