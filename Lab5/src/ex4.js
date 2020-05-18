class Animal {
    constructor(name) {
        this.name = name;
    }

    get getName() {
        return this.name;
    }

}

class Dog extends Animal {
    constructor(name) {
        super(name);
        this.created = new Date();
    }

    get getCreated() {
        return this.created;
    }
}

var dog = new Dog("Husky");
console.log("Dog name = " + dog.getName);
console.log("Dog created = " + dog.getCreated);