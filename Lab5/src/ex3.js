function minArr(array) {
    var min = array[0];

    array.forEach(element => {
        if (min > element)
            min = element;    
    });

    return min;
}