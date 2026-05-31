// Scroll to Top
window.onscroll = function() {
  const clothes_boutique_button = document.querySelector('.scroll-top-btn');
  if (document.body.scrollTop > 100 || document.documentElement.scrollTop > 100) {
    clothes_boutique_button.style.display = "block";
  } else {
    clothes_boutique_button.style.display = "none";
  }
};

document.querySelector('.scroll-top-btn a').onclick = function(event) {
  event.preventDefault();
  window.scrollTo({top: 0, behavior: 'smooth'});
};

// Slick Slider
jQuery('.banner-section .slider-for').slick({
  slidesToShow: 1,
  infinite: true,
  arrows: false,
  fade: true,
  asNavFor: '.slider-nav',
});

jQuery('.banner-section .slider-nav').slick({
  slidesToShow: 3,
  infinite: true,
  arrows: false,
  slidesToScroll: 1,
  asNavFor: '.banner-section .slider-for',
  dots: true,
  focusOnSelect: true,
  centerMode: false,
  centerPadding: '0px',
  responsive: [
    {
      breakpoint: 991,
      settings: { slidesToShow: 2 }
    },
    {
      breakpoint: 400,
      settings: { slidesToShow: 1 }
    }
  ]
});