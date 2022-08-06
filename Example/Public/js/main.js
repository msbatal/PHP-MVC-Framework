function validate() {
    if (document.getElementById('name').value.length < 3 ||  document.getElementById('comment').value.length < 3) {
        alert ('Name and Comment fields length must be a minimum of 3 characters!');
        return false;
    }
}