public class Subscriber implements Observer {
    private final String name;
    public Subscriber(String name){
        this.name = name;
    }
    @Override
    public void update(Subject s){
        Publisher p = (Publisher)s;
        System.out.println(p.getTitle() + "!!!!!" +  p.getContent() + p.getDate());
    }
    @Override
    public String toString(){
        return name;
    }
}
