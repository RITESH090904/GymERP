<h3 id="plans">Buy Membership Plans</h3>

<div class="cards">

<?php
$plans = $conn->query("SELECT * FROM plans");

while($p = $plans->fetch_assoc()){
    echo "
    <div class='card'>
        <h4>".$p['name']."</h4>
        <p>₹".$p['price']."</p>
        <p>".$p['duration']." Days</p>

        <form method='POST'>
            <input type='hidden' name='plan' value='".$p['name']."'>
            <input type='hidden' name='price' value='".$p['price']."'>
            <button name='buy'>Buy Now</button>
        </form>
    </div>
    ";
}
?>

</div>