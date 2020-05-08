async function ex6() {
    let response = await fetch("https://ghibliapi.herokuapp.com/people");
    let data = await response.json()
    return console.log(data);
}