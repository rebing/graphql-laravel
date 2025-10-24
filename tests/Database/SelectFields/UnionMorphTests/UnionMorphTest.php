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
use Rebing\GraphQL\Tests\TestCaseDatabase;

class UnionMorphTest extends TestCaseDatabase
{
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

        $result = $this->httpGraphql($query);

        self::assertArrayHasKey('data', $result);
        self::assertCount(4, $result['data']['comments']);

        // Verify comments data structure
        $comments = $result['data']['comments'];
        
        // Check that we have 4 comments (2 for products, 2 for posts)
        self::assertCount(4, $comments);
        
        // Verify commentable field contains the correct data
        foreach ($comments as $comment) {
            self::assertArrayHasKey('id', $comment);
            self::assertArrayHasKey('title', $comment);
            self::assertArrayHasKey('commentable', $comment);
            
            $commentable = $comment['commentable'];
            if (isset($commentable['title'])) {
                // This is a Post comment
                self::assertArrayHasKey('file', $commentable);
                if (isset($commentable['file'])) {
                    self::assertArrayHasKey('folder', $commentable['file']);
                }
            } elseif (isset($commentable['name'])) {
                // This is a Product comment
                self::assertArrayHasKey('file', $commentable);
                if (isset($commentable['file'])) {
                    self::assertArrayHasKey('folder', $commentable['file']);
                }
            }
        }
    }
}
