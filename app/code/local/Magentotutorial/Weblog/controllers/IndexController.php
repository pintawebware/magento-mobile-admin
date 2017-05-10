<?php
class Magentotutorial_Weblog_IndexController extends Mage_Core_Controller_Front_Action {

    public function indexAction() {
        $blogpost = Mage::getModel('weblog/blogpost')->getCollection();
        var_dump($blogpost);
    }

    public function createNewPostAction() {
        $blogpost = Mage::getModel('weblog/blogpost');
        $blogpost->setTitle('Code Post!');
        $blogpost->setPost('This post was created from code!');
        $blogpost->setCreatedAt(now());
        $blogpost->save();
        echo 'Post with ID ' . $blogpost->getId() . ' created.';
        $this->showAllBlogPostsAction();
    }

    public function readPostAction() {
        $params = $this->getRequest()->getParams();
        $blogpost = Mage::getModel('weblog/blogpost');
        echo("Loading the blogpost with an ID of ".$params['id']);
        $blogpost->load($params['id']);
        $data = $blogpost->getData();
        var_dump($data);
        $this->showAllBlogPostsAction();
    }

    public function updatePostAction() {
        $blogposts = Mage::getModel('weblog/blogpost')->getCollection();
        foreach($blogposts as $post)
        {
            if($post->getId()%2==0)
            {
                $post->setPost("This is updated by updatePostAction!!!!");
                $post->setUpdatedAt(now());
                $post->save();
            }
        }
        echo 'Posts with even number id has been updated.';
        $this->showAllBlogPostsAction();
    }

    public function deletePostAction() {
        $params = $this->getRequest()->getParams();
        $blogpost = Mage::getModel('weblog/blogpost');
        $blogpost->load($params['id']);
        echo("Deleting the blogpost with an ID of ".$params['id']."<br/>");
        $blogpost->delete();
        echo("The blogpost with an ID of ".$params['id']." has been deleted"."<br/>");

        $this->showAllBlogPostsAction();
    }

    public function showAllBlogPostsAction() {
        $posts = Mage::getModel('weblog/blogpost')->getCollection();
        echo "<table border='1'><tr><th>Post ID</th><th>Post Title</th><th>Content</th><th>Updated At</th><th>Created At</th></tr>";
        foreach($posts as $blogpost){
            echo "<tr><td>".$blogpost->getId()."</td>";
            echo "<td>".$blogpost->getTitle()."</td>";
            echo "<td>".$blogpost->getPost()."</td>";
            echo "<td>".$blogpost->getUpdatedAt()."</td>";
            echo "<td>".$blogpost->getCreatedAt()."</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}