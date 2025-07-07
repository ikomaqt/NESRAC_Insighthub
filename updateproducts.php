<?php
@include 'admin.php';
@include 'config.php';

$productid = $_GET['edit'];

if(isset($_POST['update_product'])){

   $productname = $_POST['productname'];
   $quantity = $_POST['quantity'];
   $price = $_POST['price'];
   $image = $_FILES['image']['name'];
   $image_tmp_name = $_FILES['image']['tmp_name'];
   $image_folder = 'productimg/'.$image;

   if(empty($productname) || empty($quantity) || empty($price) || empty($image)){
      $message[] = 'please fill out all!';    
   }else{

      $update_data = "UPDATE products SET productname='$productname', quantity= $quantity, price='$price', image='$image'  WHERE productid = '$productid'";
      $upload = mysqli_query($conn, $update_data);

      if($upload){
         move_uploaded_file($image_tmp_name, $image_folder);
         header('location:products.php');
      }else{
         $message[] = 'please fill out all!'; 
      }

   }
};

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <link rel="stylesheet" href="style/addproduct.css">
</head>
<body>

<?php
   if(isset($message)){
      foreach($message as $message){
         echo '<span class="message">'.$message.'</span>';
      }
   }
?>

<div class="container">


<div class="form-container centered">

   <?php
      
      $select = mysqli_query($conn, "SELECT * FROM products WHERE productid = '$productid'");
      while($row = mysqli_fetch_assoc($select)){

   ?>
   
   <form action="" method="post" enctype="multipart/form-data">
      <h3 class="title">Update the Product</h3>
      <input type="text" class="box" name="productname" value="<?php echo $row['productname']; ?>" placeholder="Enter the Product Name" >
      <input type="number" min="0" class="box" name="price" value="<?php echo $row['price']; ?>" placeholder="Enter the Product Price" >
      <input type="number" class="box" name="quantity" value="<?php echo $row['quantity']; ?>" placeholder="Enter the Product Quantity" >
      <input type="file" class="box" name="image"  accept="image/png, image/jpeg, image/jpg" >
      <input type="submit" value="Update" name="update_product" class="btn">
      <a href="products.php" class="btn">Go back</a>
   </form>
   


   <?php }; ?>

   

</div>

</div>

</body>
</html>