
function updateCharts(data) {
    // Existing chart updates...

    // Product Quantity Chart
    const productQuantityCtx = document.getElementById('product_quantity').getContext('2d');
    new Chart(productQuantityCtx, {
        type: 'bar',
        data: {
            labels: data.productQuantityNames,
            datasets: [{
                label: 'Quantity',
                data: data.productQuantityQuantities,
                backgroundColor: '#007bff'
            }]
        }
    });
}

// Initial chart rendering
document.addEventListener('DOMContentLoaded', () => {
    fetch('dashboard.php?fetch_chart_data=true')
        .then(response => response.json())
        .then(data => {
            updateCharts(data);
        });
});

