<?php require "../includes/header_public.php"; ?>

<section class="carousel">
    <div class="carousel-container">
        <div class="carousel-slide active">
            <div class="carousel-slide-bg" style="background-image:url('../assets/images/bg1.jpg');"></div>
            <img src="../assets/images/bg1.jpg" alt="Art and Craft 1">
            <div class="carousel-content">
                <h1>Mikale Art and Craft</h1>
                <p>Handmade art and craft products created with passion.</p>
                <a href="/" class="shop-btn">Enter Shop</a>
            </div>
        </div>
        <div class="carousel-slide">
            <div class="carousel-slide-bg" style="background-image:url('../assets/images/bg2.jpg');"></div>
            <img src="../assets/images/bg2.jpg" alt="Art and Craft 2">
            <div class="carousel-content">
                <h1>Discover Unique Pieces</h1>
                <p>Explore our collection of unique handmade items.</p>
                <a href="/" class="shop-btn">Shop Now</a>
            </div>
        </div>
        <div class="carousel-slide">
            <div class="carousel-slide-bg" style="background-image:url('../assets/images/bg3.jpg');"></div>
            <img src="../assets/images/bg3.jpg" alt="Art and Craft 3">
            <div class="carousel-content">
                <h1>Bring Creativity Home</h1>
                <p>Add personality and creativity to your space.</p>
                <a href="/" class="shop-btn">Browse Collection</a>
            </div>
        </div>
    </div>
    <div class="carousel-indicators">
        <span class="indicator active" data-slide="0"></span>
        <span class="indicator" data-slide="1"></span>
        <span class="indicator" data-slide="2"></span>
    </div>
    <button class="carousel-prev">&lt;</button>
    <button class="carousel-next">&gt;</button>
</section>

<section class="about-us">
    <div class="about-us-container">
        <h2>About Us</h2>
        <p>At Mikale Art and Craft, we create handmade products with passion, attention to detail, and love for local craftsmanship. Every item is designed to bring beauty and personality into your home.</p>
        <p>We believe in quality, creativity, and the joy of gifting something truly unique. Explore our collection and discover pieces made to inspire.</p>
    </div>
</section>

<script>
let currentSlide = 0;
const slides = document.querySelectorAll('.carousel-slide');
const indicators = document.querySelectorAll('.indicator');

function showSlide(index) {
    slides.forEach((slide, i) => {
        slide.classList.toggle('active', i === index);
    });
    indicators.forEach((indicator, i) => {
        indicator.classList.toggle('active', i === index);
    });
    currentSlide = index;
}

document.querySelector('.carousel-next').addEventListener('click', () => {
    currentSlide = (currentSlide + 1) % slides.length;
    showSlide(currentSlide);
});

document.querySelector('.carousel-prev').addEventListener('click', () => {
    currentSlide = (currentSlide - 1 + slides.length) % slides.length;
    showSlide(currentSlide);
});

indicators.forEach((indicator, index) => {
    indicator.addEventListener('click', () => showSlide(index));
});

// Auto slide
setInterval(() => {
    currentSlide = (currentSlide + 1) % slides.length;
    showSlide(currentSlide);
}, 5000);
</script>

<?php require "../includes/footer.php"; ?>