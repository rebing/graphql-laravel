<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\UnionMorphTests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Rebing\GraphQL\Tests\Support\Models\Comment;
use Rebing\GraphQL\Tests\Support\Models\File;
use Rebing\GraphQL\Tests\Support\Models\Folder;
use Rebing\GraphQL\Tests\Support\Models\Like;
use Rebing\GraphQL\Tests\Support\Models\Post;
use Rebing\GraphQL\Tests\Support\Models\Product;
use Rebing\GraphQL\Tests\Support\Models\User;
use Rebing\GraphQL\Tests\Support\Traits\SqlAssertionTrait;
use Rebing\GraphQL\Tests\TestCaseDatabase;

class UnionMorphTest extends TestCaseDatabase
{
    use SqlAssertionTrait;

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'query' => [
              CommentsQuery::class,
            ],
        ]);

        $app['config']->set('graphql.types', [
            FileType::class,
            FolderType::class,
            PostType::class,
            ProductType::class, 
            CommentableUnionType::class,
            CommentType::class,
        ]);

        Model::preventLazyLoading();
    }

    public function testUnionMorphEagerLoading(): void
    {

        // Create 2 products with different files and product folder
        /** @var Folder $productFolder */
        $productFolder = Folder::factory()->create(['name' => 'product']);
        
        /** @var File $productFile1 */
        $productFile1 = File::factory()->create(['folder_id' => $productFolder->id, 'name' => 'product_file_1.pdf']);
        
        /** @var File $productFile2 */
        $productFile2 = File::factory()->create(['folder_id' => $productFolder->id, 'name' => 'product_file_2.jpg']);
        
        /** @var Product $product1 */
        $product1 = Product::factory()->create(['file_id' => $productFile1->id, 'name' => 'Product 1']);
        
        /** @var Product $product2 */
        $product2 = Product::factory()->create(['file_id' => $productFile2->id, 'name' => 'Product 2']);

        // Create 2 posts with different files and post folder
        /** @var User $user */
        $user = User::factory()->create();
        
        /** @var Folder $postFolder */
        $postFolder = Folder::factory()->create(['name' => 'post']);
        
        /** @var File $postFile1 */
        $postFile1 = File::factory()->create(['folder_id' => $postFolder->id, 'name' => 'post_file_1.docx']);
        
        /** @var File $postFile2 */
        $postFile2 = File::factory()->create(['folder_id' => $postFolder->id, 'name' => 'post_file_2.png']);
        
        /** @var Post $post1 */
        $post1 = Post::factory()->create(['user_id' => $user->id, 'file_id' => $postFile1->id, 'title' => 'Post 1']);
        
        /** @var Post $post2 */
        $post2 = Post::factory()->create(['user_id' => $user->id, 'file_id' => $postFile2->id, 'title' => 'Post 2']);

        // Create comments for all products and posts using morphable relationships
        /** @var Comment $product1Comment */
        $product1Comment = Comment::factory()->create();
        $product1->commentableComments()->save($product1Comment);
        
        /** @var Comment $product2Comment */
        $product2Comment = Comment::factory()->create();
        $product2->commentableComments()->save($product2Comment);
        
        /** @var Comment $post1Comment */
        $post1Comment = Comment::factory()->create();
        $post1->commentableComments()->save($post1Comment);
        
        /** @var Comment $post2Comment */
        $post2Comment = Comment::factory()->create();
        $post2->commentableComments()->save($post2Comment);

        $query =
            /** @lang GraphQL */ <<<'GRAQPHQL'
{
  comments {
    id
    title
    body
    commentable {
      ... on Post {
        id
        title
        file {
          id
          name
          path
          folder {
            id
            name
          }
        }
      }
      ... on Product {
        id
        name
        price
        file {
          id
          name
          path
          folder {
            id
            name
          }
        }
      }
    }
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($query);

        $this->assertSqlQueries(
            /** @lang SQL */ <<<'SQL'
select "comments"."id", "comments"."title", "comments"."body", "comments"."commentable_id", "comments"."commentable_type" from "comments";
select "products"."id", "products"."name", "products"."price", "products"."file_id" from "products" where "products"."id" in (?, ?);
select "files"."id", "files"."name", "files"."path", "files"."folder_id" from "files" where "files"."id" in (?, ?);
select "folders"."id", "folders"."name" from "folders" where "folders"."id" in (?);
select "posts"."id", "posts"."file_id", "posts"."title" from "posts" where "posts"."id" in (?, ?);
select "files"."id", "files"."name", "files"."path", "files"."folder_id" from "files" where "files"."id" in (?, ?);
select "folders"."id", "folders"."name" from "folders" where "folders"."id" in (?);
SQL
        );

        $expectedResult = [
            'data' => [
                'comments' => [
                    [
                        'id' => (string) $product1Comment->id,
                        'title' => $product1Comment->title,
                        'body' => $product1Comment->body,
                        'commentable' => [
                            'id' => (string) $product1->id,
                            'name' => $product1->name,
                            'price' => $product1->price,
                            'file' => [
                                'id' => (string) $productFile1->id,
                                'name' => $productFile1->name,
                                'path' => $productFile1->path,
                                'folder' => [
                                    'id' => (string) $productFolder->id,
                                    'name' => $productFolder->name,
                                ],
                            ],
                        ],
                    ],
                    [
                        'id' => (string) $product2Comment->id,
                        'title' => $product2Comment->title,
                        'body' => $product2Comment->body,
                        'commentable' => [
                            'id' => (string) $product2->id,
                            'name' => $product2->name,
                            'price' => $product2->price,
                            'file' => [
                                'id' => (string) $productFile2->id,
                                'name' => $productFile2->name,
                                'path' => $productFile2->path,
                                'folder' => [
                                    'id' => (string) $productFolder->id,
                                    'name' => $productFolder->name,
                                ],
                            ],
                        ],
                    ],
                    [
                        'id' => (string) $post1Comment->id,
                        'title' => $post1Comment->title,
                        'body' => $post1Comment->body,
                        'commentable' => [
                            'id' => (string) $post1->id,
                            'title' => $post1->title,
                            'file' => [
                                'id' => (string) $postFile1->id,
                                'name' => $postFile1->name,
                                'path' => $postFile1->path,
                                'folder' => [
                                    'id' => (string) $postFolder->id,
                                    'name' => $postFolder->name,
                                ],
                            ],
                        ],
                    ],
                    [
                        'id' => (string) $post2Comment->id,
                        'title' => $post2Comment->title,
                        'body' => $post2Comment->body,
                        'commentable' => [
                            'id' => (string) $post2->id,
                            'title' => $post2->title,
                            'file' => [
                                'id' => (string) $postFile2->id,
                                'name' => $postFile2->name,
                                'path' => $postFile2->path,
                                'folder' => [
                                    'id' => (string) $postFolder->id,
                                    'name' => $postFolder->name,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        self::assertSame($expectedResult, $result);
    }
}
