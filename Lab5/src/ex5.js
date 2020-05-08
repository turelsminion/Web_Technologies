function ex5() {
    const fetchPromise = fetch("https://ghibliapi.herokuapp.com/people");
    fetchPromise.then(response => {
        return response.json();
    }).then(people => {
        console.log(people);
    });
}