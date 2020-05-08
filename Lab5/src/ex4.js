class Animal {
    constructor(name) {
        this.name = name;
    }

    get getName() {
        return this.name;
    }

}

class Dog extends Animal {
    constructor(name, created) {
        super(name);
        this.created = created;
    }

    get getCreated() {
        return this.created;
    }
}

var dog = new Dog("Husky", new Date());
console.log("Dog name = " + dog.getName);
console.log("Dog created = " + dog.getCreated);