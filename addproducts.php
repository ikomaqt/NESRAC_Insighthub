<?php

@include 'config.php';
@include 'admin.php';

if(isset($_POST['add_product'])){

   $productname = $_POST['productname'];
   $quantity = $_POST['quantity'];
   $price = $_POST['price'];
   $image = $_FILES['image']['name'];
   $image_tmp_name = $_FILES['image']['tmp_name'];
   $image_folder = 'productimg/'.$image;

      $insert = "INSERT INTO products(productname, quantity, price, image) VALUES('$productname', '$quantity', '$price', '$image')";
      $upload = mysqli_query($conn,$insert);
      if($upload){
         move_uploaded_file($image_tmp_name, $image_folder);
         $message[] = 'new product added successfully';
      }else{
         $message[] = 'could not add the product';
      }

};

?>


<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>admin page</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="style/addproduct.css">

</head>
<body>
   
<div class="container">

   <div class="form-container">

      <form action="<?php $_SERVER['PHP_SELF'] ?>" method="post" enctype="multipart/form-data">
         <h3>add a new product</h3>
         <input type="text" placeholder="enter product name" name="productname" class="box" required>
         <input type="number" placeholder="enter product price" name="price" class="box" required>
         <input type="number" placeholder="enter product quantity" name="quantity" class="box" required>
         <input type="file" accept="image/png, image/jpeg, image/jpg" name="image" class="box" required>
         <input type="submit" class="btn" name="add_product" value="add product">
      </form>

   </div>

   

</div>


</body>
</html>